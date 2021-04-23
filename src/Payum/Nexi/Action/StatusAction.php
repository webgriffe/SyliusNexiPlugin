<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetStatusInterface;
use Psr\Log\LoggerInterface;
use Webgriffe\LibQuiPago\Notification\Result;

final class StatusAction implements ActionInterface
{
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        if (count($details->getArrayCopy()) === 0) {
            return;
        }

        if ($details->get('esito') === Result::OUTCOME_OK) {
            $request->markCaptured();

            return;
        }

        if ($details->get('esito') === Result::OUTCOME_ANNULLO) {
            $request->markCanceled();

            return;
        }

        if (in_array($details->get('esito'), [Result::OUTCOME_KO, Result::OUTCOME_ERRORE], true)) {
            $this->logger->error(
                'Nexi payment status is not ok or canceled and will be marked as failed.',
                $details->getArrayCopy()
            );
            $request->markFailed();

            return;
        }

        $this->logger->error('Nexi payment status is invalid and will be marked as unknown.', $details->getArrayCopy());
        $request->markUnknown();
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request)
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
