<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('webgriffe_sylius_klarna_payments_plugin_payment_status', '/payment/{paymentId}/klarna-payments-status')
        ->controller(['webgriffe_sylius_klarna_payments.controller.payment', 'statusAction'])
        ->methods(['GET'])
        ->requirements(['paymentId' => '\d+'])
    ;
};
