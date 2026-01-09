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
