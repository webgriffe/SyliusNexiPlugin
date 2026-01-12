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
        if (array_key_exists('mac', $storedPaymentDetails)) {
            Assert::string($storedPaymentDetails['mac']);
        }
        if (array_key_exists('pan', $storedPaymentDetails)) {
            Assert::string($storedPaymentDetails['pan']);
        }
        if (array_key_exists('data', $storedPaymentDetails)) {
            Assert::string($storedPaymentDetails['data']);
        }
        if (array_key_exists('mail', $storedPaymentDetails)) {
            Assert::string($storedPaymentDetails['mail']);
        }
        if (array_key_exists('nome', $storedPaymentDetails)) {
            Assert::string($storedPaymentDetails['nome']);
        }
        if (array_key_exists('alias', $storedPaymentDetails)) {
            Assert::string($storedPaymentDetails['alias']);
        }
        if (array_key_exists('brand', $storedPaymentDetails)) {
            Assert::string($storedPaymentDetails['brand']);
        }
        if (array_key_exists('codAut', $storedPaymentDetails)) {
            Assert::string($storedPaymentDetails['codAut']);
        }
        if (array_key_exists('divisa', $storedPaymentDetails)) {
            Assert::string($storedPaymentDetails['divisa']);
        }
        if (array_key_exists('orario', $storedPaymentDetails)) {
            Assert::string($storedPaymentDetails['orario']);
        }
        if (array_key_exists('cognome', $storedPaymentDetails)) {
            Assert::string($storedPaymentDetails['cognome']);
        }
        if (array_key_exists('importo', $storedPaymentDetails)) {
            Assert::string($storedPaymentDetails['importo']);
        }
        if (array_key_exists('regione', $storedPaymentDetails)) {
            Assert::string($storedPaymentDetails['regione']);
        }
        if (array_key_exists('codTrans', $storedPaymentDetails)) {
            Assert::string($storedPaymentDetails['codTrans']);
        }
        if (array_key_exists('OPTION_CF', $storedPaymentDetails)) {
            Assert::string($storedPaymentDetails['OPTION_CF']);
        }
        if (array_key_exists('messaggio', $storedPaymentDetails)) {
            Assert::string($storedPaymentDetails['messaggio']);
        }
        if (array_key_exists('languageId', $storedPaymentDetails)) {
            Assert::string($storedPaymentDetails['languageId']);
        }
        if (array_key_exists('terminalId', $storedPaymentDetails)) {
            Assert::string($storedPaymentDetails['terminalId']);
        }
        if (array_key_exists('codiceEsito', $storedPaymentDetails)) {
            Assert::string($storedPaymentDetails['codiceEsito']);
        }
        if (array_key_exists('descrizione', $storedPaymentDetails)) {
            Assert::string($storedPaymentDetails['descrizione']);
        }
        if (array_key_exists('nazionalita', $storedPaymentDetails)) {
            Assert::string($storedPaymentDetails['nazionalita']);
        }
        if (array_key_exists('scadenza_pan', $storedPaymentDetails)) {
            Assert::string($storedPaymentDetails['scadenza_pan']);
        }
        if (array_key_exists('selectedcard', $storedPaymentDetails)) {
            Assert::string($storedPaymentDetails['selectedcard']);
        }
        if (array_key_exists('tipoProdotto', $storedPaymentDetails)) {
            Assert::string($storedPaymentDetails['tipoProdotto']);
        }
        if (array_key_exists('aliasEffettivo', $storedPaymentDetails)) {
            Assert::string($storedPaymentDetails['aliasEffettivo']);
        }
        if (array_key_exists('tipoTransazione', $storedPaymentDetails)) {
            Assert::string($storedPaymentDetails['tipoTransazione']);
        }
    }
}
