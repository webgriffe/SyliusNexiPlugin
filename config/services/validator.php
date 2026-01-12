<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Webgriffe\SyliusNexiPlugin\Validator\NexiPaymentMethodUniqueValidator;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_nexi.validator.nexi_payment_method_unique', NexiPaymentMethodUniqueValidator::class)
        ->args([
            service('sylius.repository.payment_method'),
        ])
        ->tag('validator.constraint_validator')
    ;
};
