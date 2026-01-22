# Upgrade plugin guide

## Upgrade from version 1.x to 2.x

To upgrade from version 1.x to 2.x of this plugin, please follow these steps:

The plugin configuration directory has been restructred to follow the Symfony best practices. You will need to move your existing configuration files to the new directory structure:

- Add the file `config/routes/webgriffe_sylius_nexi_plugin.yaml` and move your existing route configuration settings (if you have any) there. The file should look like this:

```yaml
webgriffe_sylius_nexi_plugin_shop:
    resource: "@WebgriffeSyliusNexiPlugin/config/shop_routing.php"
    prefix: /{_locale}
    requirements:
        _locale: ^[A-Za-z]{2,4}(_([A-Za-z]{4}|[0-9]{3}))?(_([A-Za-z]{2}|[0-9]{3}))?$

webgriffe_sylius_nexi_plugin_ajax:
    resource: "@WebgriffeSyliusNexiPlugin/config/shop_ajax_routing.php"
```

- Add the file `config/packages/webgriffe_sylius_nexi_plugin.yaml` and insert the following configuration settings:

```yaml
imports:
    - { resource: "@WebgriffeSyliusNexiPlugin/config/config.php" }
```

## Upgrade from version 2.x to 3.x

With this version we have removed the capture of the payment from the request of the return URL, to improve security and comply with best practices.
This requires now to specify a cancel URL for Nexi which will be used in case the user cancels the payment on Nexi side or if any general error occurs during the payment process.
Add the following route from Payum if you don't have it already:

```yaml
sylius_shop_payum_cancel:
   resource: "@PayumBundle/Resources/config/routing/cancel.xml"
```

Run:
```bash
php bin/console sylius:install:assets
```
Or, you can add the entry to your webpack.config.js file:
```javascript
    .addEntry(
        'webgriffe-sylius-nexi-entry',
        './vendor/webgriffe/sylius-nexi-plugin/public/poll_payment.js'
    )
```
And then override the template `WebgriffeSyliusNexiPlugin/Process/index.html.twig` to include the entry:
```twig
{% block javascripts %}
    {{ parent() }}

    <script>
        window.afterUrl = "{{ afterUrl }}";
        window.paymentStatusUrl = "{{ paymentStatusUrl }}";
    </script>
    {{ encore_entry_script_tags('webgriffe-sylius-nexi-entry', null, 'sylius.shop') }}
{% endblock %}
```

## Upgrade from version 3.x to 4.x

In this version, we have updated the plugin to be compatible with version 2 of Sylius.

- The route `@WebgriffeSyliusNexiPlugin/config/shop_routing.php` has been renamed to `@WebgriffeSyliusNexiPlugin/config/routes/shop.php`.
- The route `@WebgriffeSyliusNexiPlugin/config/shop_ajax_routing.php` has been renamed to `@WebgriffeSyliusNexiPlugin/config/routes/shop_ajax.php`.
- The page `@WebgriffeSyliusNexiPlugin/Process/index.html.twig` has been replaced with `@WebgriffeSyliusNexiPlugin/shop/payment/process.html.twig` and now uses twig hooks. If you have customized the previous template, please migrate your customizations to the new template using the available twig hooks.
- The asset `public/poll_payment.js` has been removed. The JS is now included in the default Webpack Encore build process.
