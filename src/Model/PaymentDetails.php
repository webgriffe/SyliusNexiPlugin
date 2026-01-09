<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Model;

/**
 * @psalm-type StoredPaymentDetails array{
 *     esito?: string,
 * }
 */
final class PaymentDetails
{
    public const OUTCOME_KEY = 'esito';

    private ?string $outcome = null;

    public function getOutcome(): ?string
    {
        return $this->outcome;
    }

    public function setOutcome(?string $outcome): void
    {
        $this->outcome = $outcome;
    }

    public function isCaptured(): bool
    {
        return $this->getOutcome() !== null;
    }

    private function __construct()
    {
    }

    /**
     * @param StoredPaymentDetails $storedPaymentDetails
     */
    public static function createFromStoredPaymentDetails(array $storedPaymentDetails): self
    {
        $paymentDetails = new self();
        if (array_key_exists(self::OUTCOME_KEY, $storedPaymentDetails)) {
            $paymentDetails->setOutcome($storedPaymentDetails[self::OUTCOME_KEY]);
        }

        return $paymentDetails;
    }
}
