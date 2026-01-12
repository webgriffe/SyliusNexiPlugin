<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Model;

/**
 * @psalm-type StoredPaymentDetails array{
 *     mac?: string,
 *     pan?: string,
 *     data?: string,
 *     mail?: string,
 *     nome?: string,
 *     alias?: string,
 *     brand?: string,
 *     esito?: string,
 *     codAut?: string,
 *     divisa?: string,
 *     orario?: string,
 *     cognome?: string,
 *     importo?: string,
 *     regione?: string,
 *     codTrans?: string,
 *     OPTION_CF?: string,
 *     messaggio?: string,
 *     languageId?: string,
 *     terminalId?: string,
 *     codiceEsito?: string,
 *     descrizione?: string,
 *     nazionalita?: string,
 *     scadenza_pan?: string,
 *     selectedcard?: string,
 *     tipoProdotto?: string,
 *     aliasEffettivo?: string,
 *     tipoTransazione?: string,
 * }
 */
final class PaymentDetails
{
    public const OUTCOME_KEY = 'esito';

    private ?string $mac = null;

    private ?string $pan = null;

    private ?string $date = null;

    private ?string $mail = null;

    private ?string $fistName = null;

    private ?string $alias = null;

    private ?string $brand = null;

    private ?string $outcome = null;

    private ?string $authorizationCode = null;

    private ?string $currency = null;

    private ?string $time = null;

    private ?string $lastName = null;

    private ?string $amount = null;

    private ?string $region = null;

    private ?string $transactionCode = null;

    private ?string $optionCF = null;

    private ?string $message = null;

    private ?string $languageId = null;

    private ?string $terminalId = null;

    private ?string $outcomeCode = null;

    private ?string $description = null;

    private ?string $nationality = null;

    private ?string $panExpiration = null;

    private ?string $selectedCard = null;

    private ?string $productType = null;

    private ?string $effectiveAlias = null;

    private ?string $transactionType = null;

    public function getMac(): ?string
    {
        return $this->mac;
    }

    public function setMac(?string $mac): void
    {
        $this->mac = $mac;
    }

    public function getPan(): ?string
    {
        return $this->pan;
    }

    public function setPan(?string $pan): void
    {
        $this->pan = $pan;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function setDate(?string $date): void
    {
        $this->date = $date;
    }

    public function getMail(): ?string
    {
        return $this->mail;
    }

    public function setMail(?string $mail): void
    {
        $this->mail = $mail;
    }

    public function getFistName(): ?string
    {
        return $this->fistName;
    }

    public function setFistName(?string $fistName): void
    {
        $this->fistName = $fistName;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function setAlias(?string $alias): void
    {
        $this->alias = $alias;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(?string $brand): void
    {
        $this->brand = $brand;
    }

    public function getOutcome(): ?string
    {
        return $this->outcome;
    }

    public function setOutcome(?string $outcome): void
    {
        $this->outcome = $outcome;
    }

    public function getAuthorizationCode(): ?string
    {
        return $this->authorizationCode;
    }

    public function setAuthorizationCode(?string $authorizationCode): void
    {
        $this->authorizationCode = $authorizationCode;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): void
    {
        $this->currency = $currency;
    }

    public function getTime(): ?string
    {
        return $this->time;
    }

    public function setTime(?string $time): void
    {
        $this->time = $time;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(?string $amount): void
    {
        $this->amount = $amount;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): void
    {
        $this->region = $region;
    }

    public function getTransactionCode(): ?string
    {
        return $this->transactionCode;
    }

    public function setTransactionCode(?string $transactionCode): void
    {
        $this->transactionCode = $transactionCode;
    }

    public function getOptionCF(): ?string
    {
        return $this->optionCF;
    }

    public function setOptionCF(?string $optionCF): void
    {
        $this->optionCF = $optionCF;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): void
    {
        $this->message = $message;
    }

    public function getLanguageId(): ?string
    {
        return $this->languageId;
    }

    public function setLanguageId(?string $languageId): void
    {
        $this->languageId = $languageId;
    }

    public function getTerminalId(): ?string
    {
        return $this->terminalId;
    }

    public function setTerminalId(?string $terminalId): void
    {
        $this->terminalId = $terminalId;
    }

    public function getOutcomeCode(): ?string
    {
        return $this->outcomeCode;
    }

    public function setOutcomeCode(?string $outcomeCode): void
    {
        $this->outcomeCode = $outcomeCode;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getNationality(): ?string
    {
        return $this->nationality;
    }

    public function setNationality(?string $nationality): void
    {
        $this->nationality = $nationality;
    }

    public function getPanExpiration(): ?string
    {
        return $this->panExpiration;
    }

    public function setPanExpiration(?string $panExpiration): void
    {
        $this->panExpiration = $panExpiration;
    }

    public function getSelectedCard(): ?string
    {
        return $this->selectedCard;
    }

    public function setSelectedCard(?string $selectedCard): void
    {
        $this->selectedCard = $selectedCard;
    }

    public function getProductType(): ?string
    {
        return $this->productType;
    }

    public function setProductType(?string $productType): void
    {
        $this->productType = $productType;
    }

    public function getEffectiveAlias(): ?string
    {
        return $this->effectiveAlias;
    }

    public function setEffectiveAlias(?string $effectiveAlias): void
    {
        $this->effectiveAlias = $effectiveAlias;
    }

    public function getTransactionType(): ?string
    {
        return $this->transactionType;
    }

    public function setTransactionType(?string $transactionType): void
    {
        $this->transactionType = $transactionType;
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
        if (array_key_exists('mac', $storedPaymentDetails)) {
            $paymentDetails->setMac($storedPaymentDetails['mac']);
        }
        if (array_key_exists('pan', $storedPaymentDetails)) {
            $paymentDetails->setPan($storedPaymentDetails['pan']);
        }
        if (array_key_exists('data', $storedPaymentDetails)) {
            $paymentDetails->setDate($storedPaymentDetails['data']);
        }
        if (array_key_exists('mail', $storedPaymentDetails)) {
            $paymentDetails->setMail($storedPaymentDetails['mail']);
        }
        if (array_key_exists('nome', $storedPaymentDetails)) {
            $paymentDetails->setFistName($storedPaymentDetails['nome']);
        }
        if (array_key_exists('alias', $storedPaymentDetails)) {
            $paymentDetails->setAlias($storedPaymentDetails['alias']);
        }
        if (array_key_exists('brand', $storedPaymentDetails)) {
            $paymentDetails->setBrand($storedPaymentDetails['brand']);
        }
        if (array_key_exists('codAut', $storedPaymentDetails)) {
            $paymentDetails->setAuthorizationCode($storedPaymentDetails['codAut']);
        }
        if (array_key_exists('divisa', $storedPaymentDetails)) {
            $paymentDetails->setCurrency($storedPaymentDetails['divisa']);
        }
        if (array_key_exists('orario', $storedPaymentDetails)) {
            $paymentDetails->setTime($storedPaymentDetails['orario']);
        }
        if (array_key_exists('cognome', $storedPaymentDetails)) {
            $paymentDetails->setLastName($storedPaymentDetails['cognome']);
        }
        if (array_key_exists('importo', $storedPaymentDetails)) {
            $paymentDetails->setAmount($storedPaymentDetails['importo']);
        }
        if (array_key_exists('regione', $storedPaymentDetails)) {
            $paymentDetails->setRegion($storedPaymentDetails['regione']);
        }
        if (array_key_exists('codTrans', $storedPaymentDetails)) {
            $paymentDetails->setTransactionCode($storedPaymentDetails['codTrans']);
        }
        if (array_key_exists('OPTION_CF', $storedPaymentDetails)) {
            $paymentDetails->setOptionCF($storedPaymentDetails['OPTION_CF']);
        }
        if (array_key_exists('messaggio', $storedPaymentDetails)) {
            $paymentDetails->setMessage($storedPaymentDetails['messaggio']);
        }
        if (array_key_exists('languageId', $storedPaymentDetails)) {
            $paymentDetails->setLanguageId($storedPaymentDetails['languageId']);
        }
        if (array_key_exists('terminalId', $storedPaymentDetails)) {
            $paymentDetails->setTerminalId($storedPaymentDetails['terminalId']);
        }
        if (array_key_exists('codiceEsito', $storedPaymentDetails)) {
            $paymentDetails->setOutcomeCode($storedPaymentDetails['codiceEsito']);
        }
        if (array_key_exists('descrizione', $storedPaymentDetails)) {
            $paymentDetails->setDescription($storedPaymentDetails['descrizione']);
        }
        if (array_key_exists('nazionalita', $storedPaymentDetails)) {
            $paymentDetails->setNationality($storedPaymentDetails['nazionalita']);
        }
        if (array_key_exists('scadenza_pan', $storedPaymentDetails)) {
            $paymentDetails->setPanExpiration($storedPaymentDetails['scadenza_pan']);
        }
        if (array_key_exists('selectedcard', $storedPaymentDetails)) {
            $paymentDetails->setSelectedCard($storedPaymentDetails['selectedcard']);
        }
        if (array_key_exists('tipoProdotto', $storedPaymentDetails)) {
            $paymentDetails->setProductType($storedPaymentDetails['tipoProdotto']);
        }
        if (array_key_exists('aliasEffettivo', $storedPaymentDetails)) {
            $paymentDetails->setEffectiveAlias($storedPaymentDetails['aliasEffettivo']);
        }
        if (array_key_exists('tipoTransazione', $storedPaymentDetails)) {
            $paymentDetails->setTransactionType($storedPaymentDetails['tipoTransazione']);
        }

        return $paymentDetails;
    }
}
