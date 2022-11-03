<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action;

use GuzzleHttp\Psr7\ServerRequest;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\Notify;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;
use Webgriffe\LibQuiPago\Lists\SignatureMethod;
use Webgriffe\LibQuiPago\Notification\Request as LibQuiPagoRequest;
use Webgriffe\LibQuiPago\Notification\Result;
use Webgriffe\LibQuiPago\Signature\Checker;
use Webgriffe\SyliusNexiPlugin\Decoder\RequestParamsDecoderInterface;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Api;

final class NotifyAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
{
    use GatewayAwareTrait, ApiAwareTrait;

    /** @var Api */
    protected $api;

    public function __construct(
        private Checker $checker,
        private RequestParamsDecoderInterface $decoder,
        private LoggerInterface $logger,
    ) {
        $this->apiClass = Api::class;
    }

    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        $httpRequest = new GetHttpRequest();
        $this->gateway->execute($httpRequest);

        /** @var SyliusPaymentInterface $payment */
        $payment = $request->getFirstModel();
        if (array_key_exists('esito', $payment->getDetails())) {
            // Already handled this payment
            return;
        }

        /** @var array<string, string> $requestParams */
        $requestParams = $httpRequest->request;
        $requestParams = $this->decoder->decode($requestParams);
        $this->logger->debug('Nexi payment notify request parameters', ['parameters' => $requestParams]);

        if ($requestParams['esito'] === Result::OUTCOME_ANNULLO) {
            $this->logger->notice('Nexi payment status from http request is cancelled.');
            $details->replace($requestParams);

            return;
        }

        $serverRequest = ServerRequest::fromGlobals();
        $this->checker->checkSignature(
            LibQuiPagoRequest::buildFromHttpRequest($serverRequest),
            $this->api->getMacKey(),
            SignatureMethod::SHA1_METHOD
        );
        $details->replace($requestParams);
    }

    public function supports($request)
    {
        return
            $request instanceof Notify &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
