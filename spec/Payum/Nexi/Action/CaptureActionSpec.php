<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayInterface;
use Payum\Core\Reply\HttpPostRedirect;
use Payum\Core\Request\Capture;
use PhpSpec\ObjectBehavior;
use Psr\Log\LoggerInterface;
use stdClass;
use Sylius\Bundle\PayumBundle\Model\PaymentSecurityTokenInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Webgriffe\LibQuiPago\Lists\SignatureMethod;
use Webgriffe\LibQuiPago\PaymentInit\Request;
use Webgriffe\LibQuiPago\Signature\Checker;
use Webgriffe\LibQuiPago\Signature\Signer;
use Webgriffe\SyliusNexiPlugin\Decoder\RequestParamsDecoderInterface;
use Webgriffe\SyliusNexiPlugin\Factory\RequestFactoryInterface;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action\CaptureAction;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Api;

final class CaptureActionSpec extends ObjectBehavior
{
    private const REQUEST_PARAMS = ['mac' => '1231231', 'amount' => '15'];

    public function let(
        Signer $signer,
        Checker $checker,
        RequestParamsDecoderInterface $decoder,
        LoggerInterface $logger,
        PaymentRepositoryInterface $paymentRepository,
        PaymentInterface $payment,
        GatewayInterface $gateway,
        OrderInterface $order,
        RequestFactoryInterface $requestFactory,
        Request $request,
    ): void {
        $payment->getDetails()->willReturn([]);

        $payment->getOrder()->willReturn($order);

        $request->getParams()->willReturn(self::REQUEST_PARAMS);

        $this->beConstructedWith(
            $signer,
            $checker,
            $decoder,
            $logger,
            $paymentRepository,
            $requestFactory,
        );
        $this->setGateway($gateway);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(CaptureAction::class);
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

    public function it_should_support_capture_requests_having_payment_as_model(PaymentInterface $payment): void
    {
        $this->supports(new Capture($payment->getWrappedObject()))->shouldReturn(true);
    }

    public function it_should_not_support_capture_requests_having_other_models(): void
    {
        $this->supports(new Capture(new stdClass()))->shouldReturn(false);
    }

    public function it_captures_request_making_an_http_post_redirect_to_nexi_to_start_payment(
        PaymentInterface $payment,
        PaymentSecurityTokenInterface $token,
        Signer $signer,
        Request $request,
        LoggerInterface $logger,
        RequestFactoryInterface $requestFactory,
        OrderInterface $order,
    ): void {
        $capture = new Capture($token->getWrappedObject());
        $capture->setModel($payment->getWrappedObject());

        $api = new Api(['sandbox' => false, 'alias' => 'ALIAS_WEB_111111', 'mac_key' => '83Y4TDI8W7Y4EWIY48TWT']);
        $this->setApi($api);

        $requestFactory->create($api, $order, $payment, $token)->willReturn($request->getWrappedObject())->shouldBeCalledOnce();

        $signer->sign($request, '83Y4TDI8W7Y4EWIY48TWT', SignatureMethod::SHA1_METHOD)->shouldBeCalledOnce();
        $logger->debug('Nexi payment request prepared for the client browser', ['request' => self::REQUEST_PARAMS])->shouldBeCalledOnce();

        $this->shouldThrow(HttpPostRedirect::class)->during('execute', [$capture]);
    }

    public function it_does_not_capture_request_if_payment_has_been_already_captured(
        PaymentInterface $payment,
        PaymentSecurityTokenInterface $token,
        Signer $signer,
        Request $request,
        LoggerInterface $logger,
        RequestFactoryInterface $requestFactory,
        OrderInterface $order,
    ): void {
        $payment->getDetails()->willReturn(['esito' => 'KO']);
        $capture = new Capture($token->getWrappedObject());
        $capture->setModel($payment->getWrappedObject());

        $api = new Api(['sandbox' => false, 'alias' => 'ALIAS_WEB_111111', 'mac_key' => '83Y4TDI8W7Y4EWIY48TWT']);
        $this->setApi($api);

        $requestFactory->create($api, $order, $payment, $token)->willReturn($request->getWrappedObject())->shouldNotBeCalled();

        $signer->sign($request, '83Y4TDI8W7Y4EWIY48TWT', SignatureMethod::SHA1_METHOD)->shouldNotBeCalled();
        $logger->debug('Nexi payment request prepared for the client browser', ['request' => self::REQUEST_PARAMS])->shouldNotBeCalled();

        $this->execute($capture)->shouldReturn(null);
    }
}
