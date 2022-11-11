<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusNexiPlugin\Behat\Context\Ui;

use Behat\Behat\Context\Context;
use Sylius\Behat\Page\Shop\Checkout\CompletePageInterface;
use Tests\Webgriffe\SyliusNexiPlugin\Behat\Service\Mocker\NexiApiMocker;

final class NexiContext implements Context
{
    public function __construct(
        private NexiApiMocker $nexiApiMocker,
        private CompletePageInterface $summaryPage,
    ) {
    }

    /**
     * @When /^I confirm my order with nexi payment$/
     * @Given /^I have confirmed my order with nexi payment$/
     */
    public function iConfirmMyOrderWithNexiPayment(): void
    {
        $this->nexiApiMocker->performActionInApiInitializeScope(function () {
            $this->summaryPage->confirmOrder();
        });
    }
}
