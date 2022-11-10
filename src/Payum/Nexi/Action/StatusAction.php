<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetStatusInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;
use Webgriffe\LibQuiPago\Notification\Result;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Api;

final class StatusAction implements ActionInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var SyliusPaymentInterface $payment */
        $payment = $request->getFirstModel();

        $details = $payment->getDetails();

        if (count($details) === 0) {
            $this->logger->warning(sprintf(
                'Unable to mark the request for payment with id "%s" from order with id "%s": the payment details are empty.',
                $payment->getId(),
                $payment->getOrder()?->getId()
            ));

            return;
        }

        $result = $details[Api::RESULT_FIELD];
        if ($result === Result::OUTCOME_OK) {
            $this->logger->info(sprintf(
                'Request captured for payment with id "%s" from order with id "%s".',
                $payment->getId(),
                $payment->getOrder()?->getId()
            ), $details);
            $request->markCaptured();

            return;
        }

        if ($result === Result::OUTCOME_ANNULLO) {
            $this->logger->notice(sprintf(
                'Request canceled for payment with id "%s" from order with id "%s".',
                $payment->getId(),
                $payment->getOrder()?->getId()
            ), $details);
            $request->markCanceled();

            return;
        }

        if (in_array($result, [Result::OUTCOME_KO, Result::OUTCOME_ERRORE], true)) {
            $this->logger->warning(sprintf(
                'Request failed for payment with id "%s" from order with id "%s".',
                $payment->getId(),
                $payment->getOrder()?->getId()
            ), $details);
            $request->markFailed();

            return;
        }
        $this->logger->warning(sprintf(
            'Request unknown for payment with id "%s" from order with id "%s". The outcome result is: "%s", the recognized status are "%s". Check the documentation if something is changed: %s.',
            $payment->getId(),
            $payment->getOrder()?->getId(),
            $result,
            implode(', ', [Result::OUTCOME_OK, Result::OUTCOME_KO, Result::OUTCOME_ERRORE, Result::OUTCOME_ANNULLO]),
            'https://ecommerce.nexi.it/specifiche-tecniche/codicebase/introduzione.html',
        ), $details);
        $request->markUnknown();
    }

    public function supports($request): bool
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getFirstModel() instanceof SyliusPaymentInterface;
    }
}
