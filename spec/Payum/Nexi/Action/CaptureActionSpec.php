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
use Payum\Core\Request\GetHttpRequest;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use stdClass;
use Sylius\Bundle\PayumBundle\Model\PaymentSecurityTokenInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Webgriffe\LibQuiPago\Lists\SignatureMethod;
use Webgriffe\LibQuiPago\Notification\Result;
use Webgriffe\LibQuiPago\PaymentInit\Request;
use Webgriffe\LibQuiPago\Signature\Checker;
use Webgriffe\LibQuiPago\Signature\Signed;
use Webgriffe\LibQuiPago\Signature\Signer;
use Webgriffe\SyliusNexiPlugin\Decoder\RequestParamsDecoderInterface;
use Webgriffe\SyliusNexiPlugin\Factory\GetHttpRequestFactoryInterface;
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
        PaymentInterface $payment,
        GatewayInterface $gateway,
        OrderInterface $order,
        RequestFactoryInterface $requestFactory,
        Request $request,
        GetHttpRequestFactoryInterface $getHttpRequestFactory,
        GetHttpRequest $getHttpRequest,
    ): void {
        $order->getId()->willReturn(1);

        $payment->getDetails()->willReturn([]);
        $payment->getId()->willReturn(2);
        $payment->getOrder()->willReturn($order);

        $request->getParams()->willReturn(self::REQUEST_PARAMS);

        $getHttpRequest->query = [];

        $getHttpRequestFactory->create()->willReturn($getHttpRequest);

        $this->beConstructedWith(
            $signer,
            $checker,
            $decoder,
            $logger,
            $requestFactory,
            $getHttpRequestFactory,
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

    public function it_captures_request_if_payment_is_canceled(
        PaymentInterface $payment,
        PaymentSecurityTokenInterface $token,
        LoggerInterface $logger,
        GetHttpRequest $getHttpRequest,
        RequestParamsDecoderInterface $decoder,
    ): void {
        $capture = new Capture($token->getWrappedObject());
        $capture->setModel($payment->getWrappedObject());

        $api = new Api(['sandbox' => false, 'alias' => 'ALIAS_WEB_111111', 'mac_key' => '83Y4TDI8W7Y4EWIY48TWT']);
        $this->setApi($api);

        $getHttpRequest->query = [Api::RESULT_FIELD => Result::OUTCOME_ANNULLO];

        $decoder->decode([Api::RESULT_FIELD => Result::OUTCOME_ANNULLO])->shouldBeCalledOnce()->willReturn([Api::RESULT_FIELD => Result::OUTCOME_ANNULLO]);
        $logger->debug('Nexi payment capture parameters', ['parameters' => [Api::RESULT_FIELD => Result::OUTCOME_ANNULLO]])->shouldBeCalledOnce();

        $logger->notice('Nexi payment status returned for payment with id "2" from order with id "1" is cancelled.')->shouldBeCalledOnce();
        $payment->setDetails([Api::RESULT_FIELD => Result::OUTCOME_ANNULLO])->shouldBeCalledOnce();

        $this->execute($capture)->shouldReturn(null);
    }

    public function it_captures_request_if_payment_is_completed(
        PaymentInterface $payment,
        PaymentSecurityTokenInterface $token,
        LoggerInterface $logger,
        GetHttpRequest $getHttpRequest,
        RequestParamsDecoderInterface $decoder,
        Checker $checker,
    ): void {
        $capture = new Capture($token->getWrappedObject());
        $capture->setModel($payment->getWrappedObject());

        $api = new Api(['sandbox' => false, 'alias' => 'ALIAS_WEB_111111', 'mac_key' => '83Y4TDI8W7Y4EWIY48TWT']);
        $this->setApi($api);

        $getHttpRequest->query = [Api::RESULT_FIELD => Result::OUTCOME_OK];

        $decoder->decode([Api::RESULT_FIELD => Result::OUTCOME_OK])->shouldBeCalledOnce()->willReturn([Api::RESULT_FIELD => Result::OUTCOME_OK]);
        $logger->debug('Nexi payment capture parameters', ['parameters' => [Api::RESULT_FIELD => Result::OUTCOME_OK]])->shouldBeCalledOnce();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['alias' => 'ALIAS_WEB_111111', 'importo' => '15', 'divisa' => 'EUR', 'codTrans' => '000001-1', 'mac' => '123456', 'esito' => 'OK', 'data' => '2022-11-09', 'orario' => '14:41:00'];
        $checker->checkSignature(Argument::type(Signed::class), '83Y4TDI8W7Y4EWIY48TWT', SignatureMethod::SHA1_METHOD)->shouldBeCalledOnce();

        $logger->info('Nexi payment status returned for payment with id "2" from order with id "1" is "OK".')->shouldBeCalledOnce();
        $payment->setDetails([Api::RESULT_FIELD => Result::OUTCOME_OK])->shouldBeCalledOnce();

        $this->execute($capture)->shouldReturn(null);
    }

    public function it_captures_request_if_payment_is_failed(
        PaymentInterface $payment,
        PaymentSecurityTokenInterface $token,
        LoggerInterface $logger,
        GetHttpRequest $getHttpRequest,
        RequestParamsDecoderInterface $decoder,
        Checker $checker,
    ): void {
        $capture = new Capture($token->getWrappedObject());
        $capture->setModel($payment->getWrappedObject());

        $api = new Api(['sandbox' => false, 'alias' => 'ALIAS_WEB_111111', 'mac_key' => '83Y4TDI8W7Y4EWIY48TWT']);
        $this->setApi($api);

        $getHttpRequest->query = [Api::RESULT_FIELD => Result::OUTCOME_KO];

        $decoder->decode([Api::RESULT_FIELD => Result::OUTCOME_KO])->shouldBeCalledOnce()->willReturn([Api::RESULT_FIELD => Result::OUTCOME_KO]);
        $logger->debug('Nexi payment capture parameters', ['parameters' => [Api::RESULT_FIELD => Result::OUTCOME_KO]])->shouldBeCalledOnce();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['alias' => 'ALIAS_WEB_111111', 'importo' => '15', 'divisa' => 'EUR', 'codTrans' => '000001-1', 'mac' => '123456', 'esito' => 'KO', 'data' => '2022-11-09', 'orario' => '14:41:00'];
        $checker->checkSignature(Argument::type(Signed::class), '83Y4TDI8W7Y4EWIY48TWT', SignatureMethod::SHA1_METHOD)->shouldBeCalledOnce();

        $logger->info('Nexi payment status returned for payment with id "2" from order with id "1" is "KO".')->shouldBeCalledOnce();
        $payment->setDetails([Api::RESULT_FIELD => Result::OUTCOME_KO])->shouldBeCalledOnce();

        $this->execute($capture)->shouldReturn(null);
    }

    public function it_captures_request_if_payment_has_error(
        PaymentInterface $payment,
        PaymentSecurityTokenInterface $token,
        LoggerInterface $logger,
        GetHttpRequest $getHttpRequest,
        RequestParamsDecoderInterface $decoder,
        Checker $checker,
    ): void {
        $capture = new Capture($token->getWrappedObject());
        $capture->setModel($payment->getWrappedObject());

        $api = new Api(['sandbox' => false, 'alias' => 'ALIAS_WEB_111111', 'mac_key' => '83Y4TDI8W7Y4EWIY48TWT']);
        $this->setApi($api);

        $getHttpRequest->query = [Api::RESULT_FIELD => Result::OUTCOME_ERRORE];

        $decoder->decode([Api::RESULT_FIELD => Result::OUTCOME_ERRORE])->shouldBeCalledOnce()->willReturn([Api::RESULT_FIELD => Result::OUTCOME_ERRORE]);
        $logger->debug('Nexi payment capture parameters', ['parameters' => [Api::RESULT_FIELD => Result::OUTCOME_ERRORE]])->shouldBeCalledOnce();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['alias' => 'ALIAS_WEB_111111', 'importo' => '15', 'divisa' => 'EUR', 'codTrans' => '000001-1', 'mac' => '123456', 'esito' => 'KO', 'data' => '2022-11-09', 'orario' => '14:41:00'];
        $checker->checkSignature(Argument::type(Signed::class), '83Y4TDI8W7Y4EWIY48TWT', SignatureMethod::SHA1_METHOD)->shouldBeCalledOnce();

        $logger->info('Nexi payment status returned for payment with id "2" from order with id "1" is "ERRORE".')->shouldBeCalledOnce();
        $payment->setDetails([Api::RESULT_FIELD => Result::OUTCOME_ERRORE])->shouldBeCalledOnce();

        $this->execute($capture)->shouldReturn(null);
    }
}
