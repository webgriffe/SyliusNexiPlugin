<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetStatusInterface;
use Psr\Log\LoggerInterface;
use Webgriffe\LibQuiPago\Notification\Result;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;

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
            $this->logger->warning('HTTP Request has not payment details');

            return;
        }

        if ($details['esito'] === Result::OUTCOME_OK) {
            $this->logger->info(
                'Nexi payment status is ok.',
                $details
            );
            $request->markCaptured();

            return;
        }

        if ($details['esito'] === Result::OUTCOME_ANNULLO) {
            $this->logger->notice(
                'Nexi payment status is cancelled.',
                $details
            );
            $request->markCanceled();

            return;
        }

        if (in_array($details['esito'], [Result::OUTCOME_KO, Result::OUTCOME_ERRORE], true)) {
            $this->logger->warning(
                'Nexi payment status is not ok or canceled and will be marked as failed.',
                $details
            );
            $request->markFailed();

            return;
        }

        $this->logger->warning(
            'Nexi payment status is invalid and will be marked as unknown.',
            $details
        );
        $request->markUnknown();
    }

    public function supports($request): bool
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getFirstModel() instanceof SyliusPaymentInterface;
    }
}
