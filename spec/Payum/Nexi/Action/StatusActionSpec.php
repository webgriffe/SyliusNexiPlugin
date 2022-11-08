<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action;

use Payum\Core\Action\ActionInterface;
use PhpSpec\ObjectBehavior;
use Psr\Log\LoggerInterface;
use stdClass;
use Sylius\Bundle\PayumBundle\Request\GetStatus;
use Sylius\Component\Core\Model\PaymentInterface;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action\StatusAction;

final class StatusActionSpec extends ObjectBehavior
{
    public function let(
        LoggerInterface $logger,
        PaymentInterface $payment,
    ): void {
        $this->beConstructedWith(
            $logger,
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(StatusAction::class);
    }

    public function it_implements_action_interface(): void
    {
        $this->shouldHaveType(ActionInterface::class);
    }

    public function it_should_support_get_status_requests_having_payment_as_model(PaymentInterface $payment): void
    {
        $this->supports(new GetStatus($payment->getWrappedObject()))->shouldReturn(true);
    }

    public function it_should_not_support_get_status_requests_having_other_models(): void
    {
        $this->supports(new GetStatus(new stdClass()))->shouldReturn(false);
    }
}
