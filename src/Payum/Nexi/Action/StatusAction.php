<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\Generic;
use Payum\Core\Request\GetStatusInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;
use Webgriffe\LibQuiPago\Notification\Result;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Api;
use Webmozart\Assert\Assert;

final class StatusAction implements ActionInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * @psalm-suppress MoreSpecificImplementedParamType
     * @param GetStatusInterface&Generic $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);
        Assert::methodExists($request, 'getFirstModel');

        /** @var SyliusPaymentInterface $payment */
        $payment = $request->getFirstModel();

        $paymentDetails = $payment->getDetails();

        if (count($paymentDetails) === 0) {
            $this->logger->warning(sprintf(
                'Unable to mark the request for payment with id "%s" from order with id "%s": the payment details are empty.',
                (string) $payment->getId(),
                (string) $payment->getOrder()?->getId()
            ));

            return;
        }
        Assert::keyExists($paymentDetails, Api::RESULT_FIELD, sprintf(
            'The key "%s" does not exists in the payment details captured, let\'s check the documentation [%s] if something has changed!',
            Api::RESULT_FIELD,
            'https://ecommerce.nexi.it/specifiche-tecniche/codicebase/introduzione.html'
        ));

        $result = (string) $paymentDetails[Api::RESULT_FIELD];
        if ($result === Result::OUTCOME_OK) {
            $this->logger->info(sprintf(
                'Request captured for payment with id "%s" from order with id "%s".',
                (string) $payment->getId(),
                (string) $payment->getOrder()?->getId()
            ), $paymentDetails);
            $request->markCaptured();

            return;
        }

        if ($result === Result::OUTCOME_ANNULLO) {
            $this->logger->notice(sprintf(
                'Request canceled for payment with id "%s" from order with id "%s".',
                (string) $payment->getId(),
                (string) $payment->getOrder()?->getId()
            ), $paymentDetails);
            $request->markCanceled();

            return;
        }

        if (in_array($result, [Result::OUTCOME_KO, Result::OUTCOME_ERRORE], true)) {
            $this->logger->warning(sprintf(
                'Request failed for payment with id "%s" from order with id "%s".',
                (string) $payment->getId(),
                (string) $payment->getOrder()?->getId()
            ), $paymentDetails);
            $request->markFailed();

            return;
        }
        $this->logger->warning(sprintf(
            'Request unknown for payment with id "%s" from order with id "%s". The outcome result is: "%s", the recognized status are "%s". Check the documentation if something is changed: %s.',
            (string) $payment->getId(),
            (string) $payment->getOrder()?->getId(),
            $result,
            implode(', ', [Result::OUTCOME_OK, Result::OUTCOME_KO, Result::OUTCOME_ERRORE, Result::OUTCOME_ANNULLO]),
            'https://ecommerce.nexi.it/specifiche-tecniche/codicebase/introduzione.html',
        ), $paymentDetails);
        $request->markUnknown();
    }

    public function supports($request): bool
    {
        return
            $request instanceof GetStatusInterface &&
            method_exists($request, 'getFirstModel') &&
            $request->getFirstModel() instanceof SyliusPaymentInterface;
    }
}
