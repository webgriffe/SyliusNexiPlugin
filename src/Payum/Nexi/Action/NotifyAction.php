<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action;

use ArrayAccess;
use GuzzleHttp\Psr7\ServerRequest;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Generic;
use Payum\Core\Request\Notify;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;
use Webgriffe\LibQuiPago\Lists\SignatureMethod;
use Webgriffe\LibQuiPago\Notification\Request as LibQuiPagoRequest;
use Webgriffe\LibQuiPago\Notification\Result;
use Webgriffe\LibQuiPago\Signature\Checker;
use Webgriffe\LibQuiPago\Signature\InvalidMacException;
use Webgriffe\SyliusNexiPlugin\Decoder\RequestParamsDecoderInterface;
use Webgriffe\SyliusNexiPlugin\Factory\GetHttpRequestFactoryInterface;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Api;
use Webmozart\Assert\Assert;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class NotifyAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
{
    use GatewayAwareTrait, ApiAwareTrait;

    /** @var Api */
    protected $api;

    public function __construct(
        private readonly Checker $checker,
        private readonly RequestParamsDecoderInterface $decoder,
        private readonly LoggerInterface $logger,
        private readonly GetHttpRequestFactoryInterface $getHttpRequestFactory,
    ) {
        $this->apiClass = Api::class;
    }

    /**
     * This action is invoked by Nexi with the Server2Server POST notify. Previously we have
     * to pass between the NotifyNullAction to resolve the Payment Token.
     * The purpose of this action is to capture the POST Nexi parameters and store them in the
     * ArrayObject details. We don't have to store the details in the payment details property
     * because is the Payum\Core\Action\ExecuteSameRequestWithModelDetailsAction to store them
     * in the main model.
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     *
     * @phpstan-ignore-next-line
     *
     * @param Notify&Generic $request
     *
     * @throws InvalidMacException
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        // This is needed to populate the http request with GET and POST params from current request
        $this->gateway->execute($httpRequest = $this->getHttpRequestFactory->create());

        /** @var SyliusPaymentInterface|mixed $payment */
        $payment = $request->getFirstModel();
        Assert::isInstanceOf($payment, SyliusPaymentInterface::class);
        if ($this->isPaymentAlreadyCaptured($payment)) {
            return;
        }

        /** @var array<string, string> $requestParameters */
        $requestParameters = $httpRequest->request;

        $this->capturePaymentDetailsFromRequestParameters($details, $payment, $requestParameters);
    }

    public function supports($request): bool
    {
        return
            $request instanceof Notify &&
            $request->getModel() instanceof ArrayAccess;
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
            'https://ecommerce.nexi.it/specifiche-tecniche/codicebase/introduzione.html',
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
