<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Webgriffe\SyliusNexiPlugin\Controller\PaymentController;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_nexi.controller.payment', PaymentController::class)
        ->args([
            service('sylius.repository.order'),
            service('request_stack'),
            service('payum.security.token_storage'),
            service('router'),
            service('sylius.repository.payment'),
        ])
        ->call('setContainer', [service('service_container')])
        ->tag('controller.service_arguments')
    ;
};
