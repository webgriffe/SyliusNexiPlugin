<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusNexiPlugin\Behat\Page\Shop\Payum\Capture;

use FriendsOfBehat\PageObjectExtension\Page\SymfonyPage;
use Webmozart\Assert\Assert;

final class PayumCaptureDoPage extends SymfonyPage implements PayumCaptureDoPageInterface
{
    public function getRouteName(): string
    {
        return 'payum_capture_do';
    }

    public function getAmount(): int
    {
        return (int) $this->getElement('amount')->getValue();
    }

    public function getAlias(): string
    {
        $value = $this->getElement('alias')->getValue();
        Assert::string($value);

        return $value;
    }

    public function getSuccessUrl(): string
    {
        $value = $this->getElement('urlSuccess')->getValue();
        Assert::string($value);

        return $value;
    }

    public function getCurrency(): string
    {
        $value = $this->getElement('currency')->getValue();
        Assert::string($value);

        return $value;
    }

    public function getPaymentCode(): string
    {
        $value = $this->getElement('paymentCode')->getValue();
        Assert::string($value);

        return $value;
    }

    public function getBackUrl(): string
    {
        $value = $this->getElement('urlBack')->getValue();
        Assert::string($value);

        return $value;
    }

    public function getPostUrl(): string
    {
        $value = $this->getElement('urlPost')->getValue();
        Assert::string($value);

        return $value;
    }

    public function getEmail(): string
    {
        $value = $this->getElement('email')->getValue();
        Assert::string($value);

        return $value;
    }

    public function getLanguageId(): string
    {
        $value = $this->getElement('languageId')->getValue();
        Assert::string($value);

        return $value;
    }

    public function getDescription(): string
    {
        $value = $this->getElement('description')->getValue();
        Assert::string($value);

        return $value;
    }

    public function getMac(): string
    {
        $value = $this->getElement('mac')->getValue();
        Assert::string($value);

        return $value;
    }

    protected function getDefinedElements(): array
    {
        return array_merge(parent::getDefinedElements(), [
            'alias' => 'form input[type="hidden"][name="alias"]',
            'amount' => 'form input[type="hidden"][name="importo"]',
            'currency' => 'form input[type="hidden"][name="divisa"]',
            'paymentCode' => 'form input[type="hidden"][name="codTrans"]',
            'urlSuccess' => 'form input[type="hidden"][name="url"]',
            'urlBack' => 'form input[type="hidden"][name="url_back"]',
            'urlPost' => 'form input[type="hidden"][name="urlpost"]',
            'email' => 'form input[type="hidden"][name="mail"]',
            'languageId' => 'form input[type="hidden"][name="languageId"]',
            'description' => 'form input[type="hidden"][name="descrizione"]',
            'mac' => 'form input[type="hidden"][name="mac"]',
        ]);
    }
}
