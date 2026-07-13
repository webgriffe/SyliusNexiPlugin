<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action;

use GuzzleHttp\Psr7\ServerRequest;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Webgriffe\LibQuiPago\Lists\SignatureMethod;
use Webgriffe\LibQuiPago\Notification\Request as LibQuiPagoRequest;
use Webgriffe\LibQuiPago\Notification\Result;
use Webgriffe\LibQuiPago\Signature\Checker;
use Webgriffe\LibQuiPago\Signature\InvalidMacException;
use Webgriffe\SyliusNexiPlugin\Decoder\RequestParamsDecoderInterface;
use Webgriffe\SyliusNexiPlugin\Helper\PaymentDetailsHelper;
use Webgriffe\SyliusNexiPlugin\Model\PaymentDetails;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Api;
use Webmozart\Assert\Assert;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
abstract class AbstractCaptureAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
{
    use GatewayAwareTrait, ApiAwareTrait;

    /**
     * @psalm-suppress NonInvariantDocblockPropertyType
     *
     * @var Api
     */
    protected $api;

    public function __construct(
        private readonly Checker $checker,
        private readonly RequestParamsDecoderInterface $decoder,
        private readonly LoggerInterface $logger,
    ) {
        $this->apiClass = Api::class;
    }

    /**
     * If previously the status action is failed then probably the payment outcome parameters
     * have been stored in the payment details. So check for them, if they exist then we can skip the
     * capture and procede to the status action.
     */
    protected function isPaymentAlreadyCaptured(PaymentInterface $payment): bool
    {
        $storedPaymentDetails = $payment->getDetails();
        if (!PaymentDetailsHelper::areValid($storedPaymentDetails)) {
            return false;
        }
        $paymentDetails = PaymentDetails::createFromStoredPaymentDetails($storedPaymentDetails);

        return $paymentDetails->isCaptured();
    }

    /**
     * This method will capture the payment outcome request parameters and store them in the model.
     *
     * @param array<string, string> $requestParams
     *
     * @throws InvalidMacException
     */
    protected function capturePaymentDetailsFromRequestParameters(ArrayObject|PaymentInterface $model, PaymentInterface $payment, array $requestParams): void
    {
        Assert::false($this->isPaymentAlreadyCaptured($payment));
        // Decode non UTF-8 characters
        $requestParams = $this->decoder->decode($requestParams);
        $this->logger->debug('Nexi payment capture parameters', ['parameters' => $requestParams]);

        if (!array_key_exists(PaymentDetails::OUTCOME_KEY, $requestParams)) {
            $this->logger->info(sprintf(
                'The key "%s" does not exist in the parameters coming back from Nexi for payment with id "%s": the request does not look like a valid Nexi callback, replying with a 400 response. If the request actually comes from Nexi, let\'s check the documentation [%s] if something has changed!',
                PaymentDetails::OUTCOME_KEY,
                (string) $payment->getId(),
                'https://ecommerce.nexi.it/specifiche-tecniche/codicebase/introduzione.html',
            ));

            throw new HttpResponse('Request not valid', 400);
        }

        $result = (string) $requestParams[PaymentDetails::OUTCOME_KEY];
        if ($result === Result::OUTCOME_ANNULLO || $result === Result::OUTCOME_ERRORE) {
            $this->logger->notice(sprintf(
                'Nexi payment status returned for payment with id "%s" from order with id "%s" is "%s".',
                (string) $payment->getId(),
                (string) $payment->getOrder()?->getId(),
                $result,
            ));
            $this->storeRequestParametersInModel($model, $requestParams);

            return;
        }
        $serverRequest = ServerRequest::fromGlobals();
        $this->checker->checkSignature(
            LibQuiPagoRequest::buildFromHttpRequest($serverRequest),
            $this->api->getMacKey(),
            SignatureMethod::SHA1_METHOD,
        );
        $this->logger->info(sprintf(
            'Nexi payment status returned for payment with id "%s" from order with id "%s" is "%s".',
            (string) $payment->getId(),
            (string) $payment->getOrder()?->getId(),
            $result,
        ));
        $this->storeRequestParametersInModel($model, $requestParams);
    }

    private function storeRequestParametersInModel(ArrayObject|PaymentInterface $model, array $parameters): void
    {
        if ($model instanceof ArrayObject) {
            $model->replace($parameters);

            return;
        }
        $model->setDetails($parameters);
    }
}
