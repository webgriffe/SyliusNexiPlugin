<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action;

use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Reply\HttpPostRedirect;
use Payum\Core\Request\Capture;
use Payum\Core\Request\Generic;
use Payum\Core\Security\TokenInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;
use Webgriffe\LibQuiPago\Lists\SignatureMethod;
use Webgriffe\LibQuiPago\Signature\Checker;
use Webgriffe\LibQuiPago\Signature\InvalidMacException;
use Webgriffe\LibQuiPago\Signature\Signer;
use Webgriffe\SyliusNexiPlugin\Decoder\RequestParamsDecoderInterface;
use Webgriffe\SyliusNexiPlugin\Factory\GetHttpRequestFactoryInterface;
use Webgriffe\SyliusNexiPlugin\Factory\RequestFactoryInterface;
use Webmozart\Assert\Assert;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class CaptureAction extends AbstractCaptureAction
{
    public function __construct(
        private Signer $signer,
        Checker $checker,
        RequestParamsDecoderInterface $decoder,
        private LoggerInterface $logger,
        private RequestFactoryInterface $requestFactory,
        private GetHttpRequestFactoryInterface $getHttpRequestFactory,
    ) {
        parent::__construct($checker, $decoder, $this->logger);
    }

    /**
     * This action is invoked by two main entries: the starting payment procedure and the return back to the store,
     * of the buyer, after a completed, cancelled or failed checkout on Nexi.
     * The purpose of this action is also to capture the payment parameters if the Server2Server POST notify
     * is not yat arrived.
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     * @phpstan-ignore-next-line
     *
     * @param Capture&Generic $request
     *
     * @throws InvalidMacException
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        // This is needed to populate the http request with GET and POST params from current request
        $this->gateway->execute($httpRequest = $this->getHttpRequestFactory->create());

        /** @var SyliusPaymentInterface|mixed $payment */
        $payment = $request->getModel();
        Assert::isInstanceOf($payment, SyliusPaymentInterface::class);
        if ($this->isPaymentAlreadyCaptured($payment)) {
            return;
        }

        /** @var array<string, string> $requestParameters */
        $requestParameters = $httpRequest->query;
        if (count($requestParameters) > 0) {
            // It is the request coming back from Nexi after the checkout, so we have to capture it
            $this->capturePaymentDetailsFromRequestParameters($payment, $payment, $requestParameters);

            return;
        }
        $order = $payment->getOrder();
        Assert::isInstanceOf($order, OrderInterface::class);

        $token = $request->getToken();
        Assert::isInstanceOf($token, TokenInterface::class);

        $nexiRequest = $this->requestFactory->create($this->api->getMerchantAlias(), $payment, $token);

        $this->signer->sign($nexiRequest, $this->api->getMacKey(), SignatureMethod::SHA1_METHOD);
        $this->logger->debug('Nexi payment request prepared for the client browser', ['request' => $nexiRequest->getParams()]);

        throw new HttpPostRedirect(
            $this->api->getApiEndpoint(),
            $nexiRequest->getParams()
        );
    }

    public function supports($request): bool
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof SyliusPaymentInterface;
    }
}
