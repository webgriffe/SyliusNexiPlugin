<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action;

use ArrayAccess;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\Generic;
use Payum\Core\Request\Notify;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;
use Webgriffe\LibQuiPago\Signature\Checker;
use Webgriffe\LibQuiPago\Signature\InvalidMacException;
use Webgriffe\SyliusNexiPlugin\Decoder\RequestParamsDecoderInterface;
use Webgriffe\SyliusNexiPlugin\Factory\GetHttpRequestFactoryInterface;
use Webmozart\Assert\Assert;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
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
}
