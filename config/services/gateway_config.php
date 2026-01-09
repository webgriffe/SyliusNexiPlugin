<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Payum\Core\Bridge\Symfony\Builder\GatewayFactoryBuilder;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Api;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\NexiGatewayFactory;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_nexi.gateway_factory_builder', GatewayFactoryBuilder::class)
        ->args([
            NexiGatewayFactory::class,
        ])
        ->tag('payum.gateway_factory_builder', ['factory' => Api::CODE])
    ;
};
