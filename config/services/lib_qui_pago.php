<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Webgriffe\LibQuiPago\Signature\DefaultChecker;
use Webgriffe\LibQuiPago\Signature\DefaultSigner;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_nexi.lib.signer', DefaultSigner::class)
        ->args([
            service('webgriffe_sylius_nexi.logger'),
        ])
    ;

    $services->set('webgriffe_sylius_nexi.lib.checker', DefaultChecker::class)
        ->args([
            service('webgriffe_sylius_nexi.logger'),
        ])
    ;
};
