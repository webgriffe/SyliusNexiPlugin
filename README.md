<p align="center">
    <a href="https://sylius.com" target="_blank">
        <picture>
          <source media="(prefers-color-scheme: dark)" srcset="https://media.sylius.com/sylius-logo-800-dark.png">
          <source media="(prefers-color-scheme: light)" srcset="https://media.sylius.com/sylius-logo-800.png">
          <img alt="Sylius Logo." src="https://media.sylius.com/sylius-logo-800.png">
        </picture>
    </a>
</p>

<h1 align="center">Sylius Nexi Plugin</h1>

The _SyliusNexiPlugin_ provides an integration between [Sylius](https://sylius.com/) and [Nexi XPay](https://developer.nexi.it/it/servizio-ecommerce) payment gateway.
This plugin implements the [Hosted Payment Page](https://developer.nexi.it/it/modalita-di-integrazione/hosted-payment-page) integration method.

> Note! This plugin is only compatible with the Nexi configuration which allows only one payment per request. It is
> therefore not possible to retry the payment several times on the Nexi checkout,
> so make sure that your Nexi gateway is configured to not allow payment retry (you'll have to ask to Nexi customer care for this)!

## Installation

1. Run:
    ```bash
    composer require webgriffe/sylius-nexi-plugin
   ```

2. Add `Webgriffe\SyliusNexiPlugin\WebgriffeSyliusNexiPlugin::class => ['all' => true]` to your `config/bundles.php`.
   
   Normally, the plugin is automatically added to the `config/bundles.php` file by the `composer require` command. If it is not, you have to add it manually.

3. Create a new file config/packages/webgriffe_sylius_nexi_plugin.yaml:
   ```yaml
   imports:
       - { resource: "@WebgriffeSyliusNexiPlugin/config/config.php" }
   ```

4. Import the routes needed. Add the following to your config/routes.yaml file:
   ```yaml
   webgriffe_sylius_nexi_plugin_shop:
       resource: "@WebgriffeSyliusNexiPlugin/config/routes/shop.php"
       prefix: /{_locale}
       requirements:
           _locale: ^[A-Za-z]{2,4}(_([A-Za-z]{4}|[0-9]{3}))?(_([A-Za-z]{2}|[0-9]{3}))?$

   webgriffe_sylius_nexi_plugin_ajax:
       resource: "@WebgriffeSyliusNexiPlugin/config/routes/shop_ajax.php"

   sylius_shop_payum_cancel:
       resource: "@PayumBundle/Resources/config/routing/cancel.xml"

   ```
   **NB:** The file shop_routing needs to be after the prefix _locale, so that messages can be displayed in the right
   language. You should also include the cancel routes from the Payum bundle if you do not have it already!

5. Run:
    ```bash
    php bin/console sylius:install:assets
   ```

## Usage

Go in your Sylius admin panel and create a new payment method. Choose `Nexi Gateway` as the payment gateway and fill the required fields.
You can also enable the _Sandbox_ mode if you want to test the integration with the [Nexi test environment](https://developer.nexi.it/it/area-test/introduzione).

## Contributing

For a comprehensive guide on Sylius Plugins development please go to Sylius documentation,
there you will find the <a href="https://docs.sylius.com/plugins-development-guide/how-to-create-a-plugin-for-sylius">Plugin Development Guide</a> - it's a great place to start.

For more information about the **Test Application** included in the skeleton, please refer to the [Sylius documentation](https://docs.sylius.com/plugins-development-guide/test-application).

### Traditional

1. From the plugin skeleton root directory, run the following commands:
   
    ```bash
    (cd vendor/sylius/test-application && yarn install)
    (cd vendor/sylius/test-application && yarn build)
    vendor/bin/console assets:install
   
    vendor/bin/console doctrine:database:create
    vendor/bin/console doctrine:migrations:migrate -n
    # Optionally load data fixtures
    vendor/bin/console sylius:fixtures:load -n
    ```

To be able to set up a plugin's database, remember to configure your database credentials in `tests/TestApplication/.env` and `tests/TestApplication/.env.test`.

2. Run your local server:
   
      ```bash
      symfony server:ca:install
      symfony server:start -d
      ```

3. Open your browser and navigate to `https://localhost:8000`.

### Docker

1. Execute `make init` to initialize the container and install the dependencies.

2. Execute `make database-init` to create the database and run migrations.

3. (Optional) Execute `make load-fixtures` to load the fixtures.

4. Your app is available at `http://localhost`.

## Usage

### Running plugin tests

- PHPUnit
  
  ```bash
  vendor/bin/phpunit
  ```

- Behat (non-JS scenarios)
  
  ```bash
  vendor/bin/behat --strict --tags="~@javascript&&~@mink:chromedriver"
  ```

- Behat (JS scenarios)
    
    1. [Install Symfony CLI command](https://symfony.com/download).
    
    2. Start Headless Chrome:
  
    ```bash
    google-chrome-stable --enable-automation --disable-background-networking --no-default-browser-check --no-first-run --disable-popup-blocking --disable-default-apps --allow-insecure-localhost --disable-translate --disable-extensions --no-sandbox --enable-features=Metal --headless --remote-debugging-port=9222 --window-size=2880,1800 --proxy-server='direct://' --proxy-bypass-list='*' http://127.0.0.1
    ```
    
    3. Install SSL certificates (only once needed) and run test application's webserver on `127.0.0.1:8080`:
  
    ```bash
    symfony server:ca:install
    APP_ENV=test symfony server:start --port=8080 --daemon
    ```
    
    4. Run Behat:
  
    ```bash
    vendor/bin/behat --strict --tags="@javascript,@mink:chromedriver"
    ```

- Static Analysis
    
    - PHPStan
      
      ```bash
      vendor/bin/phpstan analyse -c phpstan.neon -l max src/  
      ```
    
    - Psalm
      
      ```bash
      vendor/bin/psalm
      ```

- Coding Standard
  
  ```bash
  vendor/bin/ecs check
  ```

### Opening Sylius with your plugin

- Using `test` environment:
  
    ```bash
    APP_ENV=test vendor/bin/console vendor/bin/console sylius:fixtures:load -n
    APP_ENV=test symfony server:start -d
    ```

- Using `dev` environment:
  
    ```bash
    vendor/bin/console vendor/bin/console sylius:fixtures:load -n
    symfony server:start -d
    ```
