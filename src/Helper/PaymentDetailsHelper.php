<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Helper;

use Webgriffe\SyliusNexiPlugin\Model\PaymentDetails;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

/**
 * @psalm-suppress TypeDoesNotContainType
 *
 * @psalm-import-type StoredPaymentDetails from PaymentDetails
 */
final class PaymentDetailsHelper
{
    /**
     * @phpstan-assert-if-true StoredPaymentDetails $storedPaymentDetails
     */
    public static function areValid(array $storedPaymentDetails): bool
    {
        try {
            self::assertStoredPaymentDetailsAreValid($storedPaymentDetails);
        } catch (InvalidArgumentException) {
            return false;
        }

        return true;
    }

    /**
     * @phpstan-assert StoredPaymentDetails $storedPaymentDetails
     *
     * @throws InvalidArgumentException
     */
    public static function assertStoredPaymentDetailsAreValid(array $storedPaymentDetails): void
    {
        if (array_key_exists(PaymentDetails::OUTCOME_KEY, $storedPaymentDetails)) {
            Assert::stringNotEmpty($storedPaymentDetails[PaymentDetails::OUTCOME_KEY]);
        }
    }
}
