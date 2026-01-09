<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpPostRedirect;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Request\Capture;
use Payum\Core\Request\Generic;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;
use Payum\Core\Security\TokenInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Webgriffe\LibQuiPago\Lists\SignatureMethod;
use Webgriffe\LibQuiPago\Signature\InvalidMacException;
use Webgriffe\LibQuiPago\Signature\Signer;
use Webgriffe\SyliusNexiPlugin\Controller\PaymentController;
use Webgriffe\SyliusNexiPlugin\Factory\GetHttpRequestFactoryInterface;
use Webgriffe\SyliusNexiPlugin\Factory\RequestFactoryInterface;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Api;
use Webmozart\Assert\Assert;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class CaptureAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface, GenericTokenFactoryAwareInterface
{
    use GatewayAwareTrait, ApiAwareTrait, GenericTokenFactoryAwareTrait;

    /** @var Api */
    protected $api;

    public function __construct(
        private readonly Signer $signer,
        private readonly LoggerInterface $logger,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly GetHttpRequestFactoryInterface $getHttpRequestFactory,
        private readonly RequestStack $requestStack,
        private readonly RouterInterface $router,
    ) {
        $this->apiClass = Api::class;
    }

    /**
     * This action is invoked by two main entries: the starting payment procedure and the return back to the store,
     * of the buyer, after a completed, cancelled or failed checkout on Nexi.
     * The purpose of this action is also to capture the payment parameters if the Server2Server POST notify
     * is not yat arrived.
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     *
     * @phpstan-ignore-next-line
     *
     * @param Capture&Generic|mixed $request
     *
     * @throws InvalidMacException
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);
        Assert::isInstanceOf($request, Capture::class);

        // This is needed to populate the http request with GET and POST params from current request
        $this->gateway->execute($httpRequest = $this->getHttpRequestFactory->create());

        /** @var SyliusPaymentInterface|mixed $payment */
        $payment = $request->getModel();
        Assert::isInstanceOf($payment, SyliusPaymentInterface::class);

        /** @var string|int $paymentId */
        $paymentId = $payment->getId();
        $this->logger->info(sprintf(
            'Start capture action for Sylius payment with ID "%s".',
            $paymentId,
        ));

        $captureToken = $request->getToken();
        Assert::isInstanceOf($captureToken, TokenInterface::class);

        /** @var array<string, string> $requestParameters */
        $requestParameters = $httpRequest->query;

        $storedPaymentDetails = $payment->getDetails();
        if ($storedPaymentDetails !== [] || count($requestParameters) > 0) {
            // It is the request coming back from Nexi after the checkout or another strange case,
            // anyway we don't more capture the payment from the user request, we redirect the user to the
            // waiting page while attending for the Server2Server notify

            $this->logger->info(sprintf(
                'Here it is the capture action called back from Nexi for payment id "%d" or maybe the payment is already captured, redirecting to the waiting processing page.',
                (string) $payment->getId(),
            ));

            $session = $this->requestStack->getSession();
            $session->set(PaymentController::PAYMENT_ID_SESSION_KEY, $paymentId);
            $session->set(PaymentController::TOKEN_HASH_SESSION_KEY, $captureToken->getHash());

            $order = $payment->getOrder();
            Assert::isInstanceOf($order, OrderInterface::class);

            throw new HttpRedirect(
                $this->router->generate('webgriffe_sylius_nexi_plugin_payment_process', [
                    'tokenValue' => $order->getTokenValue(),
                    '_locale' => $order->getLocaleCode(),
                ]),
            );
        }
        $order = $payment->getOrder();
        Assert::isInstanceOf($order, OrderInterface::class);

        $token = $request->getToken();
        Assert::isInstanceOf($token, TokenInterface::class);

        $notifyToken = $this->tokenFactory->createNotifyToken($token->getGatewayName(), $token->getDetails());
        $nexiRequest = $this->requestFactory->create($this->api->getMerchantAlias(), $payment, $token, $notifyToken);

        $this->signer->sign($nexiRequest, $this->api->getMacKey(), SignatureMethod::SHA1_METHOD);
        $this->logger->debug('Nexi payment request prepared for the client browser', ['request' => $nexiRequest->getParams()]);

        throw new HttpPostRedirect(
            $this->api->getApiEndpoint(),
            $nexiRequest->getParams(),
        );
    }

    public function supports($request): bool
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof SyliusPaymentInterface;
    }
}
