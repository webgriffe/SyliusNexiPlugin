<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayInterface;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\GetToken;
use Payum\Core\Request\Notify;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Webgriffe\SyliusNexiPlugin\Factory\GetHttpRequestFactoryInterface;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action\NotifyNullAction;

final class NotifyNullActionSpec extends ObjectBehavior
{
    public function let(
        LoggerInterface $logger,
        GetHttpRequestFactoryInterface $getHttpRequestFactory,
        GetHttpRequest $getHttpRequest,
        GatewayInterface $gateway,
    ): void {
        $getHttpRequestFactory->create()->willReturn($getHttpRequest);

        $this->beConstructedWith(
            $logger,
            $getHttpRequestFactory,
        );
        $this->setGateway($gateway);
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

    public function it_captures_notify_null_by_executing_the_capturing_notify_on_the_resolved_token(
        LoggerInterface $logger,
        GetHttpRequest $getHttpRequest,
        GatewayInterface $gateway,
    ): void {
        $getHttpRequest->query = ['notify_token' => 'f3rfefs'];

        $gateway->execute($getHttpRequest)->shouldBeCalledOnce();

        $logger->debug('Nexi notify null action request.', ['queryParameters' => ['notify_token' => 'f3rfefs']])->shouldBeCalledOnce();

        $gateway->execute(Argument::type(GetToken::class))->shouldBeCalledOnce();

        $gateway->execute(Argument::type(Notify::class))->shouldBeCalledOnce();

        $this->execute(new Notify(null))->shouldReturn(null);
    }

    public function it_does_not_capture_if_request_does_not_have_notify_token(
        LoggerInterface $logger,
        GetHttpRequest $getHttpRequest,
        GatewayInterface $gateway,
    ): void {
        $getHttpRequest->query = [];

        $gateway->execute($getHttpRequest)->shouldBeCalledOnce();

        $logger->debug('Nexi notify null action request.', ['queryParameters' => []])->shouldBeCalledOnce();

        $gateway->execute(Argument::type(GetToken::class))->shouldNotBeCalled();

        $gateway->execute(Argument::type(Notify::class))->shouldNotBeCalled();

        $this->shouldThrow(HttpResponse::class)->during('execute', [new Notify(null)]);
    }

    public function it_does_not_capture_if_request_have_empty_notify_token(
        LoggerInterface $logger,
        GetHttpRequest $getHttpRequest,
        GatewayInterface $gateway,
    ): void {
        $getHttpRequest->query = ['notify_token' => ''];

        $gateway->execute($getHttpRequest)->shouldBeCalledOnce();

        $logger->debug('Nexi notify null action request.', ['queryParameters' => ['notify_token' => '']])->shouldBeCalledOnce();

        $gateway->execute(Argument::type(GetToken::class))->shouldNotBeCalled();

        $gateway->execute(Argument::type(Notify::class))->shouldNotBeCalled();

        $this->shouldThrow(HttpResponse::class)->during('execute', [new Notify(null)]);
    }
}
