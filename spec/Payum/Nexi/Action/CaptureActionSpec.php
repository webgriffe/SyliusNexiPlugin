<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\Request\Capture;
use PhpSpec\ObjectBehavior;
use Psr\Log\LoggerInterface;
use stdClass;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webgriffe\LibQuiPago\Signature\Checker;
use Webgriffe\LibQuiPago\Signature\Signer;
use Webgriffe\SyliusNexiPlugin\Decoder\RequestParamsDecoderInterface;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action\CaptureAction;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Api;

final class CaptureActionSpec extends ObjectBehavior
{
    public function let(
        Signer $signer,
        Checker $checker,
        RequestParamsDecoderInterface $decoder,
        LoggerInterface $logger,
        PaymentRepositoryInterface $paymentRepository,
        UrlGeneratorInterface $urlGenerator,
        PaymentInterface $payment,
    ): void {
        $this->beConstructedWith(
            $signer,
            $checker,
            $decoder,
            $logger,
            $paymentRepository,
            $urlGenerator,
        );
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
}
