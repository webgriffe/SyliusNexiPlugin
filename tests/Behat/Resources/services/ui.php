<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Tests\Webgriffe\SyliusNexiPlugin\Behat\Context\Ui\NexiContext;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();
    $services->defaults()->public();

    $services->set('webgriffe_sylius_nexi.behat.context.ui.nexi', NexiContext::class)
        ->args([
            service('webgriffe_sylius_nexi.behat.page.shop.payum.capture.do'),
            service('sylius.repository.payment_security_token'),
            service('sylius.repository.payment'),
            service('router'),
            service('behat.mink.default_session'),
            service('sylius.behat.page.shop.order.show'),
            service('webgriffe_sylius_nexi.behat.page.shop.payment.process'),
            service('sylius.behat.page.shop.order.thank_you'),
            service('sylius.repository.order'),
            service('sylius.behat.page.shop.order.show'),
        ])
    ;
};
