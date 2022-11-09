<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusNexiPlugin\Factory;

use Payum\Core\Security\TokenInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webgriffe\LibQuiPago\PaymentInit\Request;
use Webgriffe\SyliusNexiPlugin\Factory\RequestFactory;
use PhpSpec\ObjectBehavior;
use Webgriffe\SyliusNexiPlugin\Factory\RequestFactoryInterface;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Api;

class RequestFactorySpec extends ObjectBehavior
{
    public function let(
        UrlGeneratorInterface $urlGenerator,
        OrderInterface $order,
        PaymentInterface $payment,
        TokenInterface $token,
        CustomerInterface $customer,
    ): void {
        $customer->getEmail()->willReturn('customer@email.com');

        $order->getLocaleCode()->willReturn('it_IT');
        $order->getCustomer()->willReturn($customer);
        $order->getNumber()->willReturn('000001');

        $payment->getId()->willReturn(1);
        $payment->getAmount()->willReturn(1500);

        $token->getTargetUrl()->willReturn('https://target.url/');
        $token->getHash()->willReturn('HASH_TOKEN');

        $urlGenerator->generate(
            'payum_notify_do_unsafe',
            ['gateway' => 'nexi', 'notify_token' => 'HASH_TOKEN'],
            UrlGeneratorInterface::ABSOLUTE_URL
        )->willReturn('https://notify.url/unsafe');

        $this->beConstructedWith($urlGenerator);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(RequestFactory::class);
    }

    public function it_implements_request_factory_interface(): void
    {
        $this->shouldHaveType(RequestFactoryInterface::class);
    }

    public function it_creates_request(
        OrderInterface $order,
        PaymentInterface $payment,
        TokenInterface $token,
    ): void
    {
        $this->create(
            new Api(['sandbox' => false, 'alias' => 'ALIAS_WEB_111111', 'mac_key' => '83Y4TDI8W7Y4EWIY48TWT']),
            $order,
            $payment,
            $token
        )->shouldReturnAnInstanceOf(Request::class);
    }
}
