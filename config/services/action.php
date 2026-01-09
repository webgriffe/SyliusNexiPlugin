<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action\CaptureAction;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action\NotifyAction;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action\NotifyNullAction;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action\StatusAction;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Api;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_nexi.action.capture', CaptureAction::class)
        ->public()
        ->args([
            service('webgriffe_sylius_nexi.lib.signer'),
            service('webgriffe_sylius_nexi.logger'),
            service('webgriffe_sylius_nexi.factory.request'),
            service('webgriffe_sylius_nexi.factory.get_http_request'),
            service('request_stack'),
            service('router'),
        ])
        ->tag('payum.action', ['factory' => Api::CODE, 'alias' => 'payum.action.capture'])
    ;

    $services->set('webgriffe_sylius_nexi.action.status', StatusAction::class)
        ->public()
        ->args([
            service('webgriffe_sylius_nexi.logger'),
        ])
    ;

    $services->set('webgriffe_sylius_nexi.action.notify', NotifyAction::class)
        ->public()
        ->args([
            service('webgriffe_sylius_nexi.lib.checker'),
            service('webgriffe_sylius_nexi.decoder.request_params'),
            service('webgriffe_sylius_nexi.logger'),
            service('webgriffe_sylius_nexi.factory.get_http_request'),
        ])
        ->tag('payum.action', ['factory' => Api::CODE, 'alias' => 'payum.action.notify'])
    ;

    $services->set('webgriffe_sylius_nexi.action.notify_null', NotifyNullAction::class)
        ->public()
        ->args([
            service('webgriffe_sylius_nexi.logger'),
            service('webgriffe_sylius_nexi.factory.get_http_request'),
        ])
        ->tag('payum.action', ['factory' => Api::CODE, 'alias' => 'payum.action.notify_null'])
    ;
};
