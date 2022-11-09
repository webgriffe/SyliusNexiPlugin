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
use Payum\Core\Request\Notify;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;
use Webgriffe\LibQuiPago\Lists\SignatureMethod;
use Webgriffe\LibQuiPago\Notification\Request as LibQuiPagoRequest;
use Webgriffe\LibQuiPago\Notification\Result;
use Webgriffe\LibQuiPago\Signature\Checker;
use Webgriffe\SyliusNexiPlugin\Decoder\RequestParamsDecoderInterface;
use Webgriffe\SyliusNexiPlugin\Factory\GetHttpRequestFactoryInterface;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Api;
use Webmozart\Assert\Assert;

final class NotifyAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
{
    use GatewayAwareTrait, ApiAwareTrait;

    /** @var Api */
    protected $api;

    public function __construct(
        private Checker $checker,
        private RequestParamsDecoderInterface $decoder,
        private LoggerInterface $logger,
        private GetHttpRequestFactoryInterface $getHttpRequestFactory,
    ) {
        $this->apiClass = Api::class;
    }

    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        // This is needed to populate the http request with GET and POST params from current request
        $this->gateway->execute($httpRequest = $this->getHttpRequestFactory->create());

        /** @var SyliusPaymentInterface $payment */
        $payment = $request->getFirstModel();
        Assert::isInstanceOf($payment, SyliusPaymentInterface::class);
        if (array_key_exists(Api::RESULT_FIELD, $payment->getDetails())) {
            // Already handled this payment
            return;
        }

        /** @var array<string, string> $parameters */
        $parameters = $httpRequest->request;
        // Decode non UTF-8 characters
        $parameters = $this->decoder->decode($parameters);
        $this->logger->debug('Nexi payment notify body parameters', ['parameters' => $parameters]);

        $result = $parameters[Api::RESULT_FIELD];
        if ($result === Result::OUTCOME_ANNULLO) {
            $this->logger->notice(sprintf(
                'Nexi payment status returned for payment with id "%s" from order with id "%s" is cancelled.',
                $payment->getId(),
                $payment->getOrder()?->getId()
            ));
            $details->replace($parameters);

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
            $payment->getId(),
            $payment->getOrder()?->getId(),
            $result,
        ));
        $details->replace($parameters);
    }

    public function supports($request): bool
    {
        return
            $request instanceof Notify &&
            $request->getModel() instanceof ArrayAccess;
    }
}
