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
            'webgriffe_sylius_nexi.payment.process' => [
                'content' => [
                    'template' => '@WebgriffeSyliusNexiPlugin/shop/payment/process/content.html.twig',
                    'priority' => 0,
                ],
            ],
            'webgriffe_sylius_nexi.payment.process.content' => [
                'content' => [
                    'template' => '@WebgriffeSyliusNexiPlugin/shop/payment/process/content/loading.html.twig',
                    'priority' => 0,
                ],
            ],
            'webgriffe_sylius_nexi.payment.process#javascripts' => [
                'scripts' => [
                    'template' => '@WebgriffeSyliusNexiPlugin/shop/payment/scripts.html.twig',
                    'priority' => 0,
                ],
            ],
            'sylius_shop.base.footer.content' => [
                'payment_methods' => [
                    'template' => '@WebgriffeSyliusNexiPlugin/shop/shared/layout/base/footer/content/payment_methods.html.twig',
                    'priority' => 100,
                ],
            ],
        ],
    ]);
};
