<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusNexiPlugin\Behat\Context;

use Sylius\Bundle\PayumBundle\Model\PaymentSecurityTokenInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Webmozart\Assert\Assert;

trait PayumPaymentTrait
{
    abstract protected function getPaymentRepository(): PaymentRepositoryInterface;

    /**
     * @return RepositoryInterface<PaymentSecurityTokenInterface>
     */
    abstract protected function getPaymentTokenRepository(): RepositoryInterface;

    private function getCurrentPayment(): PaymentInterface
    {
        /** @var PaymentInterface[] $payments */
        $payments = $this->getPaymentRepository()->findBy(['state' => PaymentInterface::STATE_NEW]);
        Assert::count($payments, 1);
        $payment = reset($payments);

        return $payment;
    }

    /**
     * @return array{PaymentSecurityTokenInterface, PaymentSecurityTokenInterface}
     */
    private function getCurrentPaymentSecurityTokens(PaymentInterface $payment): array
    {
        $allPaymentTokens = $this->getPaymentTokenRepository()->findAll();
        $paymentSecurityTokens = array_filter($allPaymentTokens, static function (PaymentSecurityTokenInterface $token) use ($payment): bool {
            return $token->getDetails()->getId() === $payment->getId() &&
                $token->getDetails()->getClass() === get_class($payment)
            ;
        });
        Assert::count($paymentSecurityTokens, 3, sprintf('Expected 4 payment security tokens, got %s.', count($paymentSecurityTokens)));

        $paymentCaptureSecurityToken = $this->extractCaptureSecurityToken($paymentSecurityTokens);
        $paymentNotifySecurityToken = $this->extractNotifySecurityToken($paymentSecurityTokens);

        return [$paymentCaptureSecurityToken, $paymentNotifySecurityToken];
    }

    /**
     * @param PaymentSecurityTokenInterface[] $paymentSecurityTokens
     */
    private function extractCaptureSecurityToken(array $paymentSecurityTokens): PaymentSecurityTokenInterface
    {
        $paymentCaptureSecurityTokens = array_filter($paymentSecurityTokens, static function (PaymentSecurityTokenInterface $token): bool {
            return str_contains($token->getTargetUrl(), 'payment/capture');
        });
        Assert::count($paymentCaptureSecurityTokens, 1, sprintf('Expected 1 payment capture security token, got %s.', count($paymentCaptureSecurityTokens)));

        return array_pop($paymentCaptureSecurityTokens);
    }

    /**
     * @param PaymentSecurityTokenInterface[] $paymentSecurityTokens
     */
    private function extractNotifySecurityToken(array $paymentSecurityTokens): PaymentSecurityTokenInterface
    {
        $paymentNotifySecurityTokens = array_filter($paymentSecurityTokens, static function (PaymentSecurityTokenInterface $token): bool {
            return str_contains($token->getTargetUrl(), 'payment/notify');
        });
        Assert::count($paymentNotifySecurityTokens, 1, sprintf('Expected 1 payment notify security token, got %s.', count($paymentNotifySecurityTokens)));

        return array_pop($paymentNotifySecurityTokens);
    }

    /**
     * @param PaymentSecurityTokenInterface[] $paymentSecurityTokens
     */
    private function extractCancelSecurityToken(array $paymentSecurityTokens): PaymentSecurityTokenInterface
    {
        $paymentCancelSecurityTokens = array_filter($paymentSecurityTokens, static function (PaymentSecurityTokenInterface $token): bool {
            return str_contains($token->getTargetUrl(), 'payment/cancel');
        });
        Assert::count($paymentCancelSecurityTokens, 1, sprintf('Expected 1 payment cancel security token, got %s.', count($paymentCancelSecurityTokens)));

        return array_pop($paymentCancelSecurityTokens);
    }
}
