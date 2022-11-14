<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusNexiPlugin\Behat\Page\Shop\Payum\Capture;

use FriendsOfBehat\PageObjectExtension\Page\SymfonyPage;

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
        return $this->getElement('alias')->getValue();
    }

    public function getSuccessUrl(): string
    {
        return $this->getElement('urlSuccess')->getValue();
    }

    public function getCurrency(): string
    {
        return $this->getElement('currency')->getValue();
    }

    public function getPaymentCode(): string
    {
        return $this->getElement('paymentCode')->getValue();
    }

    public function getBackUrl(): string
    {
        return $this->getElement('urlBack')->getValue();
    }

    public function getPostUrl(): string
    {
        return $this->getElement('urlPost')->getValue();
    }

    public function getEmail(): string
    {
        return $this->getElement('email')->getValue();
    }

    public function getLanguageId(): string
    {
        return $this->getElement('languageId')->getValue();
    }

    public function getDescription(): string
    {
        return $this->getElement('description')->getValue();
    }

    public function getMac(): string
    {
        return $this->getElement('mac')->getValue();
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
