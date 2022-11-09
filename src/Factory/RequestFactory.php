<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Factory;

use Payum\Core\Security\TokenInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webgriffe\LibQuiPago\PaymentInit\Request;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Api;
use Webmozart\Assert\Assert;

final class RequestFactory implements RequestFactoryInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function create(Api $api, OrderInterface $order, PaymentInterface $payment, TokenInterface $token): Request
    {
        $customer = $order->getCustomer();
        Assert::isInstanceOf($customer, CustomerInterface::class);

        $transactionCode = $order->getNumber() . '-' . $payment->getId();

        $amount = $payment->getAmount();
        Assert::integer($amount);

        return new Request(
            $api->getMerchantAlias(),
            $amount / 100,
            $transactionCode,
            $token->getTargetUrl(),
            $customer->getEmail(),
            $token->getTargetUrl(),
            null,
            $this->mapLocaleCodeToNexiLocaleCode($order->getLocaleCode()),
            $this->urlGenerator->generate(
                'payum_notify_do_unsafe',
                ['gateway' => 'nexi', 'notify_token' => $token->getHash()],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            null,
            null,
            '#' . $order->getNumber()
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
