<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Payum\Nexi;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

final class NexiGatewayFactory extends GatewayFactory
{
    protected function populateConfig(ArrayObject $config): void
    {
        $config->defaults(
            [
                'payum.factory_name' => 'nexi',
                'payum.factory_title' => 'Nexi Payment',
                'payum.action.status' => '@webgriffe_sylius_nexi.action.status',
            ]
        );

        if (false === (bool) $config['payum.api']) {
            $config['payum.default_options'] = ['sandbox' => true];
            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = ['alias', 'mac_key', 'sandbox'];

            $config['payum.api'] = static fn (\ArrayObject $config): Api => new Api((array) $config);
        }
    }
}
