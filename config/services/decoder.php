<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Webgriffe\SyliusNexiPlugin\Decoder\RequestParamsDecoder;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_nexi.decoder.request_params', RequestParamsDecoder::class);
};
