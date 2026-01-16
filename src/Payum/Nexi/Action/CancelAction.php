<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action;

use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\Cancel;
use Payum\Core\Request\Generic;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;
use Webgriffe\LibQuiPago\Signature\Checker;
use Webgriffe\SyliusNexiPlugin\Decoder\RequestParamsDecoderInterface;
use Webgriffe\SyliusNexiPlugin\Factory\GetHttpRequestFactoryInterface;
use Webmozart\Assert\Assert;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class CancelAction extends AbstractCaptureAction
{
    public function __construct(
        Checker $checker,
        RequestParamsDecoderInterface $decoder,
        private readonly LoggerInterface $logger,
        private readonly GetHttpRequestFactoryInterface $getHttpRequestFactory,
    ) {
        parent::__construct(
            $checker,
            $decoder,
            $this->logger,
        );
    }

    /**
     * This action is invoked by Nexi only when the user cancels the payment on Nexi
     *
     * @param (Cancel&Generic)|mixed $request
     */
    #[\Override]
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);
        Assert::isInstanceOf($request, Cancel::class);

        $payment = $request->getModel();
        Assert::isInstanceOf($payment, SyliusPaymentInterface::class);

        /** @var string|int $paymentId */
        $paymentId = $payment->getId();

        $this->logger->info(sprintf(
            'Start cancel action for Sylius payment with ID "%s".',
            $paymentId,
        ));

        // This is needed to populate the http request with GET and POST params from current request
        $this->gateway->execute($httpRequest = $this->getHttpRequestFactory->create());

        /** @var SyliusPaymentInterface|mixed $payment */
        $payment = $request->getFirstModel();
        Assert::isInstanceOf($payment, SyliusPaymentInterface::class);
        if ($this->isPaymentAlreadyCaptured($payment)) {
            return;
        }

        /** @var array<string, string> $requestParameters */
        $requestParameters = $httpRequest->query;

        $this->capturePaymentDetailsFromRequestParameters($payment, $payment, $requestParameters);
    }

    #[\Override]
    public function supports($request): bool
    {
        return $request instanceof Cancel &&
            $request->getModel() instanceof SyliusPaymentInterface
        ;
    }
}
