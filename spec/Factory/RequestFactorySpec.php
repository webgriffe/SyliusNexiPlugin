<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusNexiPlugin\Factory;

use Payum\Core\Security\TokenInterface;
use PhpSpec\ObjectBehavior;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Webgriffe\LibQuiPago\PaymentInit\Request;
use Webgriffe\SyliusNexiPlugin\Factory\RequestFactory;
use Webgriffe\SyliusNexiPlugin\Factory\RequestFactoryInterface;

class RequestFactorySpec extends ObjectBehavior
{
    public function let(
        OrderInterface $order,
        PaymentInterface $payment,
        TokenInterface $token,
        TokenInterface $notifyToken,
        CustomerInterface $customer,
    ): void {
        $customer->getEmail()->willReturn('customer@email.com');

        $order->getLocaleCode()->willReturn('it_IT');
        $order->getCustomer()->willReturn($customer);
        $order->getNumber()->willReturn('000001');

        $payment->getOrder()->willReturn($order);
        $payment->getId()->willReturn(1);
        $payment->getAmount()->willReturn(1500);

        $token->getTargetUrl()->willReturn('https://target.url/');
        $token->getHash()->willReturn('HASH_TOKEN');
        $token->getGatewayName()->willReturn('nexi_payment_method');

        $notifyToken->getTargetUrl()->willReturn('https://notify.url?notify_token=HASH_TOKEN');

        $this->beConstructedWith();
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
        PaymentInterface $payment,
        TokenInterface $token,
        TokenInterface $notifyToken,
    ): void {
        $this->create(
            'ALIAS_WEB_111111',
            $payment,
            $token,
            $notifyToken,
        )->shouldReturnAnInstanceOf(Request::class);
    }
}
