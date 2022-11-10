<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action;

use ArrayAccess;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\Notify;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;
use Webgriffe\LibQuiPago\Signature\Checker;
use Webgriffe\SyliusNexiPlugin\Decoder\RequestParamsDecoderInterface;
use Webgriffe\SyliusNexiPlugin\Factory\GetHttpRequestFactoryInterface;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Api;
use Webmozart\Assert\Assert;

final class NotifyAction extends AbstractCaptureAction
{
    public function __construct(
        Checker $checker,
        RequestParamsDecoderInterface $decoder,
        LoggerInterface $logger,
        private GetHttpRequestFactoryInterface $getHttpRequestFactory,
    ) {
        parent::__construct($checker, $decoder, $logger);
    }

    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        // This is needed to populate the http request with GET and POST params from current request
        $this->gateway->execute($httpRequest = $this->getHttpRequestFactory->create());

        /** @var SyliusPaymentInterface|mixed $payment */
        $payment = $request->getFirstModel();
        Assert::isInstanceOf($payment, SyliusPaymentInterface::class);
        if (array_key_exists(Api::RESULT_FIELD, $payment->getDetails())) {
            // Already handled this payment
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
}
