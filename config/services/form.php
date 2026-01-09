<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Webgriffe\SyliusNexiPlugin\Form\Type\NexiConfigurationType;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Api;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_nexi.form.type.gateway_configuration', NexiConfigurationType::class)
        ->tag('sylius.gateway_configuration_type', ['type' => Api::CODE, 'label' => 'Nexi Gateway'])
        ->tag('form.type')
    ;
};
