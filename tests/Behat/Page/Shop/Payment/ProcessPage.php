<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusNexiPlugin\Behat\Page\Shop\Payment;

use Behat\Mink\Element\DocumentElement;
use FriendsOfBehat\PageObjectExtension\Page\SymfonyPage;

final class ProcessPage extends SymfonyPage implements ProcessPageInterface
{
    public function getRouteName(): string
    {
        return 'webgriffe_sylius_nexi_plugin_payment_process';
    }

    public function waitForRedirect(): void
    {
        $this->getDocument()->waitFor(6, function (DocumentElement $document) {
            return !str_contains($document->getContent(), 'Your payment is being processed');
        });
    }
}
