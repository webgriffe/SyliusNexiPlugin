<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action;

use Payum\Core\Action\ActionInterface;
use PhpSpec\ObjectBehavior;
use Psr\Log\LoggerInterface;
use stdClass;
use Sylius\Bundle\PayumBundle\Request\GetStatus;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Webgriffe\LibQuiPago\Notification\Result;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action\StatusAction;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Api;

final class StatusActionSpec extends ObjectBehavior
{
    public function let(
        LoggerInterface $logger,
        PaymentInterface $payment,
        OrderInterface $order,
    ): void {
        $order->getId()->willReturn(1);

        $payment->getId()->willReturn(2);
        $payment->getOrder()->willReturn($order);

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

    public function it_does_nothing_if_payment_details_are_empty(
        PaymentInterface $payment,
        LoggerInterface $logger,
    ): void {
        $payment->getDetails()->willReturn([]);

        $logger->warning('Unable to mark the request for payment with id "2" from order with id "1": the payment details are empty.')->shouldBeCalledOnce();

        $this->execute(new GetStatus($payment->getWrappedObject()))->shouldReturn(null);
    }

    public function it_marks_captured_the_request_if_payment_result_is_successfully(
        PaymentInterface $payment,
        LoggerInterface $logger,
    ): void {
        $payment->getDetails()->willReturn([Api::RESULT_FIELD => Result::OUTCOME_OK]);

        $logger->info('Request captured for payment with id "2" from order with id "1".', [Api::RESULT_FIELD => Result::OUTCOME_OK])->shouldBeCalledOnce();

        $this->execute(new GetStatus($payment->getWrappedObject()))->shouldReturn(null);
    }

    public function it_marks_canceled_the_request_if_payment_result_is_canceled(
        PaymentInterface $payment,
        LoggerInterface $logger,
    ): void {
        $payment->getDetails()->willReturn([Api::RESULT_FIELD => Result::OUTCOME_ANNULLO]);

        $logger->notice('Request canceled for payment with id "2" from order with id "1".', [Api::RESULT_FIELD => Result::OUTCOME_ANNULLO])->shouldBeCalledOnce();

        $this->execute(new GetStatus($payment->getWrappedObject()))->shouldReturn(null);
    }

    public function it_marks_failed_the_request_if_payment_result_is_failed(
        PaymentInterface $payment,
        LoggerInterface $logger,
    ): void {
        $payment->getDetails()->willReturn([Api::RESULT_FIELD => Result::OUTCOME_KO]);

        $logger->warning('Request failed for payment with id "2" from order with id "1".', [Api::RESULT_FIELD => Result::OUTCOME_KO])->shouldBeCalledOnce();

        $this->execute(new GetStatus($payment->getWrappedObject()))->shouldReturn(null);
    }

    public function it_marks_failed_the_request_if_payment_result_is_error(
        PaymentInterface $payment,
        LoggerInterface $logger,
    ): void {
        $payment->getDetails()->willReturn([Api::RESULT_FIELD => Result::OUTCOME_ERRORE]);

        $logger->warning('Request failed for payment with id "2" from order with id "1".', [Api::RESULT_FIELD => Result::OUTCOME_ERRORE])->shouldBeCalledOnce();

        $this->execute(new GetStatus($payment->getWrappedObject()))->shouldReturn(null);
    }

    public function it_marks_unknown_the_request_if_payment_result_is_not_recognized(
        PaymentInterface $payment,
        LoggerInterface $logger,
    ): void {
        $payment->getDetails()->willReturn([Api::RESULT_FIELD => 'other']);

        $logger->warning('Request unknown for payment with id "2" from order with id "1". The outcome result is: "other", the recognized status are "OK, KO, ERRORE, ANNULLO". Check the documentation if something is changed: https://ecommerce.nexi.it/specifiche-tecniche/codicebase/introduzione.html.', [Api::RESULT_FIELD => 'other'])->shouldBeCalledOnce();

        $this->execute(new GetStatus($payment->getWrappedObject()))->shouldReturn(null);
    }
}
