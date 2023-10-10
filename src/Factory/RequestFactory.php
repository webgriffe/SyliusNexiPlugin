<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Factory;

use Payum\Core\Security\TokenInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Webgriffe\LibQuiPago\PaymentInit\Request;
use Webmozart\Assert\Assert;

final class RequestFactory implements RequestFactoryInterface
{
    public function create(
        string $merchantAlias,
        PaymentInterface $payment,
        TokenInterface $token,
        TokenInterface $notifyToken
    ): Request {
        $order = $payment->getOrder();
        Assert::isInstanceOf($order, OrderInterface::class);

        $customer = $order->getCustomer();
        Assert::isInstanceOf($customer, CustomerInterface::class);

        $transactionCode = (string) $order->getNumber() . '-' . (string) $payment->getId();

        $amount = $payment->getAmount();
        Assert::integer($amount);

        return new Request(
            $merchantAlias,
            $amount / 100,
            $transactionCode,
            $token->getTargetUrl(),
            $customer->getEmail(),
            $token->getTargetUrl(),
            null,
            $this->mapLocaleCodeToNexiLocaleCode($order->getLocaleCode()),
            $notifyToken->getTargetUrl(),
            null,
            null,
            '#' . (string) $order->getNumber()
        );
    }

    private function mapLocaleCodeToNexiLocaleCode(?string $localeCode): string
    {
        return match (strtolower(substr((string) $localeCode, 0, 2))) {
            'it' => 'ITA',
            'es' => 'SPA',
            'fr' => 'FRA',
            'de' => 'GER',
            'ja' => 'JPN',
            'cn' => 'CHI',
            'ar' => 'ARA',
            'ru' => 'RUS',
            'pt' => 'POR',
            default => 'ENG',
        };
    }
}
