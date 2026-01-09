<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Controller;

use Payum\Core\Model\Identity;
use Payum\Core\Security\TokenInterface;
use Payum\Core\Storage\StorageInterface;
use Sylius\Bundle\PayumBundle\Model\GatewayConfigInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Webgriffe\SyliusNexiPlugin\Helper\PaymentDetailsHelper;
use Webgriffe\SyliusNexiPlugin\Model\PaymentDetails;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Api;
use Webmozart\Assert\Assert;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class PaymentController extends AbstractController
{
    public const PAYMENT_ID_SESSION_KEY = 'webgriffe_nexi_payment_id';

    public const TOKEN_HASH_SESSION_KEY = 'webgriffe_nexi_token_hash';

    /**
     * @param OrderRepositoryInterface<OrderInterface> $orderRepository
     * @param PaymentRepositoryInterface<PaymentInterface> $paymentRepository
     */
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly RequestStack $requestStack,
        private readonly StorageInterface $tokenStorage,
        private readonly RouterInterface $router,
        private readonly PaymentRepositoryInterface $paymentRepository,
    ) {
    }

    public function processAction(string $tokenValue): Response
    {
        $session = $this->requestStack->getSession();
        $paymentId = $session->get(self::PAYMENT_ID_SESSION_KEY);
        $hash = $session->get(self::TOKEN_HASH_SESSION_KEY);

        if (!is_string($hash) || $hash === '') {
            throw $this->createNotFoundException();
        }
        if ((!is_string($paymentId) && !is_int($paymentId)) || $paymentId === '') {
            throw $this->createNotFoundException();
        }
        $token = $this->tokenStorage->find($hash);
        if (!$token instanceof TokenInterface) {
            throw $this->createNotFoundException();
        }

        $paymentIdentity = $token->getDetails();
        Assert::isInstanceOf($paymentIdentity, Identity::class);

        $order = $this->orderRepository->findOneBy(['tokenValue' => $tokenValue]);
        if (!$order instanceof OrderInterface) {
            throw $this->createNotFoundException();
        }
        $syliusPayment = null;
        foreach ($order->getPayments() as $orderPayment) {
            if ($orderPayment->getId() === $paymentIdentity->getId()) {
                $syliusPayment = $orderPayment;

                break;
            }
        }
        if (!$syliusPayment instanceof PaymentInterface) {
            throw $this->createNotFoundException();
        }
        $storedPaymentDetails = $syliusPayment->getDetails();
        if (!PaymentDetailsHelper::areValid($storedPaymentDetails)) {
            throw $this->createNotFoundException();
        }
        $paymentStatusUrl = $this->router->generate(
            'webgriffe_sylius_nexi_plugin_payment_status',
            ['paymentId' => $syliusPayment->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        return $this->render('@WebgriffeSyliusNexiPlugin/Process/index.html.twig', [
            'afterUrl' => $token->getAfterUrl(),
            'paymentStatusUrl' => $paymentStatusUrl,
        ]);
    }

    public function statusAction(mixed $paymentId): Response
    {
        /** @var PaymentInterface|mixed $payment */
        $payment = $this->paymentRepository->find($paymentId);
        Assert::nullOrIsInstanceOf($payment, PaymentInterface::class);
        if ($payment === null) {
            throw $this->createNotFoundException();
        }
        $paymentMethod = $payment->getMethod();
        if (!$paymentMethod instanceof PaymentMethodInterface) {
            throw $this->createAccessDeniedException();
        }
        $paymentGatewayConfig = $paymentMethod->getGatewayConfig();
        if (!$paymentGatewayConfig instanceof GatewayConfigInterface) {
            throw $this->createAccessDeniedException();
        }
        /** @psalm-suppress DeprecatedMethod */
        if ($paymentGatewayConfig->getFactoryName() !== Api::CODE) {
            throw $this->createAccessDeniedException();
        }

        $storedPaymentDetails = $payment->getDetails();
        if (!PaymentDetailsHelper::areValid($storedPaymentDetails)) {
            throw $this->createAccessDeniedException();
        }
        $paymentDetails = PaymentDetails::createFromStoredPaymentDetails($storedPaymentDetails);

        return $this->json(['captured' => $paymentDetails->isCaptured()]);
    }
}
