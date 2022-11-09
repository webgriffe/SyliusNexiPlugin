<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action;

use ArrayAccess;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayInterface;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\Notify;
use PhpSpec\ObjectBehavior;
use Psr\Log\LoggerInterface;
use stdClass;
use Sylius\Bundle\PayumBundle\Model\PaymentSecurityTokenInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Webgriffe\LibQuiPago\Notification\Result;
use Webgriffe\LibQuiPago\Signature\Checker;
use Webgriffe\SyliusNexiPlugin\Decoder\RequestParamsDecoderInterface;
use Webgriffe\SyliusNexiPlugin\Factory\GetHttpRequestFactoryInterface;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action\NotifyAction;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Api;

final class NotifyActionSpec extends ObjectBehavior
{
    public function let(
        Checker $checker,
        RequestParamsDecoderInterface $decoder,
        LoggerInterface $logger,
        PaymentInterface $payment,
        GetHttpRequestFactoryInterface $getHttpRequestFactory,
        GetHttpRequest $getHttpRequest,
        GatewayInterface $gateway,
        OrderInterface $order,
    ): void {
        $getHttpRequestFactory->create()->willReturn($getHttpRequest);

        $order->getId()->willReturn(1);

        $payment->getId()->willReturn(2);
        $payment->getDetails()->willReturn([]);
        $payment->getOrder()->willReturn($order);

        $this->beConstructedWith(
            $checker,
            $decoder,
            $logger,
            $getHttpRequestFactory,
        );
        $this->setGateway($gateway);
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

    public function it_does_not_capture_notify_if_payment_has_been_already_captured(
        PaymentInterface $payment,
    ): void {
        $payment->getDetails()->willReturn([Api::RESULT_FIELD => Result::OUTCOME_KO]);
        $notify = new Notify($payment->getWrappedObject());
        $notify->setModel(new ArrayObject());

        $this->execute($notify)->shouldReturn(null);
    }

    public function it_captures_notify_if_payment_is_canceled(
        PaymentInterface $payment,
        PaymentSecurityTokenInterface $token,
        LoggerInterface $logger,
        GetHttpRequest $getHttpRequest,
        RequestParamsDecoderInterface $decoder,
        ArrayObject $details,
    ): void {
        $notify = new Notify($payment->getWrappedObject());
        $notify->setModel($details->getWrappedObject());

        $api = new Api(['sandbox' => false, 'alias' => 'ALIAS_WEB_111111', 'mac_key' => '83Y4TDI8W7Y4EWIY48TWT']);
        $this->setApi($api);

        $getHttpRequest->request = [Api::RESULT_FIELD => Result::OUTCOME_ANNULLO];

        $decoder->decode([Api::RESULT_FIELD => Result::OUTCOME_ANNULLO])->shouldBeCalledOnce()->willReturn([Api::RESULT_FIELD => Result::OUTCOME_ANNULLO]);
        $logger->debug('Nexi payment notify body parameters', ['parameters' => [Api::RESULT_FIELD => Result::OUTCOME_ANNULLO]])->shouldBeCalledOnce();

        $logger->notice('Nexi payment status returned for payment with id "2" from order with id "1" is cancelled.')->shouldBeCalledOnce();
        $details->replace([Api::RESULT_FIELD => Result::OUTCOME_ANNULLO])->shouldBeCalledOnce();

        $this->execute($notify)->shouldReturn(null);
    }
}
