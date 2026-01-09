<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Webgriffe\SyliusNexiPlugin\Factory\GetHttpRequestFactory;
use Webgriffe\SyliusNexiPlugin\Factory\RequestFactory;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_nexi.factory.request', RequestFactory::class);

    $services->set('webgriffe_sylius_nexi.factory.get_http_request', GetHttpRequestFactory::class);
};
