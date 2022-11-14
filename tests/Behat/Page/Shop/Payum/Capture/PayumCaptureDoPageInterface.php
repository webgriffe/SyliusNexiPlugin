<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusNexiPlugin\Behat\Page\Shop\Payum\Capture;

use FriendsOfBehat\PageObjectExtension\Page\SymfonyPageInterface;
use Sylius\Component\Core\Model\PaymentInterface;

interface PayumCaptureDoPageInterface extends SymfonyPageInterface
{
    public function getAmount(): int;

    public function getAlias(): string;

    public function getSuccessUrl(): string;

    public function getCurrency(): string;

    public function getPaymentCode(): string;

    public function getBackUrl(): string;

    public function getPostUrl(): string;

    public function getEmail(): string;

    public function getLanguageId(): string;

    public function getDescription(): string;

    public function getMac(): string;
}
