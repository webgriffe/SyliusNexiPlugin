<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusNexiPlugin\Behat\Page\Shop\Payment;

interface ProcessPageInterface
{
    public function waitForRedirect(): void;
}
