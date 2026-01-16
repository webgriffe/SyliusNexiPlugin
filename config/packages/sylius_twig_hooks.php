<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('sylius_twig_hooks', [
        'hooks' => [
            'sylius_admin.payment_method.create.content.form.sections.gateway_configuration' => [
                'alias' => [
                    'template' => '@WebgriffeSyliusNexiPlugin/admin/payment_method/form/alias.html.twig',
                    'priority' => 0,
                ],
                'mac_key' => [
                    'template' => '@WebgriffeSyliusNexiPlugin/admin/payment_method/form/mac_key.html.twig',
                    'priority' => 0,
                ],
                'sandbox' => [
                    'template' => '@WebgriffeSyliusNexiPlugin/admin/payment_method/form/sandbox.html.twig',
                    'priority' => 0,
                ],
            ],
            'sylius_admin.payment_method.update.content.form.sections.gateway_configuration' => [
                'alias' => [
                    'template' => '@WebgriffeSyliusNexiPlugin/admin/payment_method/form/alias.html.twig',
                    'priority' => 0,
                ],
                'mac_key' => [
                    'template' => '@WebgriffeSyliusNexiPlugin/admin/payment_method/form/mac_key.html.twig',
                    'priority' => 0,
                ],
                'sandbox' => [
                    'template' => '@WebgriffeSyliusNexiPlugin/admin/payment_method/form/sandbox.html.twig',
                    'priority' => 0,
                ],
            ],
        ],
    ]);
};
