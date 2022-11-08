<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\Request\Notify;
use PhpSpec\ObjectBehavior;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action\NotifyNullAction;

final class NotifyNullActionSpec extends ObjectBehavior
{
    public function let(
        LoggerInterface $logger,
    ): void {
        $this->beConstructedWith(
            $logger,
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(NotifyNullAction::class);
    }

    public function it_implements_action_interface(): void
    {
        $this->shouldHaveType(ActionInterface::class);
    }

    public function it_implements_gateway_aware_interface(): void
    {
        $this->shouldHaveType(GatewayAwareInterface::class);
    }

    public function it_should_support_notify_requests_having_null_model(): void
    {
        $this->supports(new Notify(null))->shouldReturn(true);
    }

    public function it_should_not_support_notify_requests_having_other_models(PaymentInterface $payment): void
    {
        $this->supports(new Notify($payment->getWrappedObject()))->shouldReturn(false);
    }
}
