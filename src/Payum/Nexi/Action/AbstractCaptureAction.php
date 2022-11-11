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
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Webgriffe\LibQuiPago\Lists\SignatureMethod;
use Webgriffe\LibQuiPago\Notification\Request as LibQuiPagoRequest;
use Webgriffe\LibQuiPago\Notification\Result;
use Webgriffe\LibQuiPago\Signature\Checker;
use Webgriffe\LibQuiPago\Signature\InvalidMacException;
use Webgriffe\SyliusNexiPlugin\Decoder\RequestParamsDecoderInterface;
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
        private Checker $checker,
        private RequestParamsDecoderInterface $decoder,
        private LoggerInterface $logger,
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
        if (array_key_exists(Api::RESULT_FIELD, $payment->getDetails())) {
            // Already handled this payment
            return true;
        }

        return false;
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

        Assert::keyExists($requestParams, Api::RESULT_FIELD, sprintf(
            'The key "%s" does not exists in the parameters coming back from Nexi, let\'s check the documentation [%s] if something has changed!',
            Api::RESULT_FIELD,
            'https://ecommerce.nexi.it/specifiche-tecniche/codicebase/introduzione.html'
        ));

        $result = (string) $requestParams[Api::RESULT_FIELD];
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
            SignatureMethod::SHA1_METHOD
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
