<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action;

use ArrayAccess;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\Model\ArrayObject;
use Payum\Core\Request\Notify;
use PhpSpec\ObjectBehavior;
use Psr\Log\LoggerInterface;
use stdClass;
use Sylius\Component\Core\Model\PaymentInterface;
use Webgriffe\LibQuiPago\Signature\Checker;
use Webgriffe\SyliusNexiPlugin\Decoder\RequestParamsDecoderInterface;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action\NotifyAction;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Api;

final class NotifyActionSpec extends ObjectBehavior
{
    public function let(
        Checker $checker,
        RequestParamsDecoderInterface $decoder,
        LoggerInterface $logger,
        PaymentInterface $payment,
    ): void {
        $this->beConstructedWith(
            $checker,
            $decoder,
            $logger,
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(NotifyAction::class);
    }

    public function it_implements_action_interface(): void
    {
        $this->shouldHaveType(ActionInterface::class);
    }

    public function it_implements_api_aware_interface(): void
    {
        $this->shouldHaveType(ApiAwareInterface::class);
    }

    public function it_implements_gateway_aware_interface(): void
    {
        $this->shouldHaveType(GatewayAwareInterface::class);
    }

    public function it_should_accept_nexi_api(): void
    {
        $this->setApi(new Api([]))->shouldReturn(null);
    }

    public function it_should_not_accept_other_apis(): void
    {
        $this->shouldThrow(UnsupportedApiException::class)->during(
            'setApi', [new stdClass()]
        );
    }

    public function it_should_support_notify_requests_having_array_access_as_model(ArrayAccess $arrayAccess): void
    {
        $this->supports(new Notify(new ArrayObject()))->shouldReturn(true);
    }

    public function it_should_not_support_notify_requests_having_other_models(PaymentInterface $payment): void
    {
        $this->supports(new Notify($payment->getWrappedObject()))->shouldReturn(false);
    }
}
