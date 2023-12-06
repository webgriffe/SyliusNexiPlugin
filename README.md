<p align="center">
    <a href="https://sylius.com" target="_blank">
        <img src="https://demo.sylius.com/assets/shop/img/logo.png" />
    </a>
</p>

<h1 align="center">Sylius Nexi Plugin</h1>

The _SyliusNexiPlugin_ provides an integration between [Sylius](https://sylius.com/) and [Nexi XPay](https://developer.nexi.it/it/servizio-ecommerce) payment gateway.
This plugin implements the [Hosted Payment Page](https://developer.nexi.it/it/modalita-di-integrazione/hosted-payment-page) integration method.

> Note! This plugin is only compatible with the Nexi configuration which allows only one payment per request. It is
> therefore not possible to retry the payment several times on the Nexi checkout,
> so make sure that your Nexi gateway is configured to not allow payment retry (you'll have to ask to Nexi customer care for this)!

## Installation

1. Run
    ```shell
    composer require webgriffe/sylius-nexi-plugin
    ```

2. Add `Webgriffe\SyliusNexiPlugin\WebgriffeSyliusNexiPlugin::class => ['all' => true]` to your `config/bundles.php`.

3. (Optional) It's suggested to also install the [Payum Lock Request Extension Bundle](https://github.com/webgriffe/PayumLockRequestExtensionBundle):
    ```shell
    composer require webgriffe/payum-lock-request-extension-bundle
    ```
    and add `Webgriffe\PayumLockRequestExtensionBundle\WebgriffePayumLockRequestExtensionBundle::class => ['all' => true]` to your `config/bundles.php`.
    This Payum extension avoids issues when concurrent requests are made by the buyer and the Nexi gateway for the same payment.

## Configuration

Go in your Sylius admin panel and create a new payment method. Choose `Nexi Gateway` as the payment gateway and fill the required fields.
You can also enable the _Sandbox_ mode if you want to test the integration with the [Nexi test environment](https://developer.nexi.it/it/area-test/introduzione).

## Contributing

### Running plugin tests

- PHPUnit

  ```bash
  vendor/bin/phpunit
  ```

- PHPSpec

  ```bash
  vendor/bin/phpspec run
  ```

- Behat (non-JS scenarios)

  ```bash
  vendor/bin/behat --strict --tags="~@javascript"
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
    APP_ENV=test symfony server:start --port=8080 --dir=tests/Application/public --daemon
    ```

    4. Run Behat:

    ```bash
    vendor/bin/behat --strict --tags="@javascript"
    ```

- Static Analysis

    - Psalm

      ```bash
      vendor/bin/psalm
      ```

    - PHPStan

      ```bash
      vendor/bin/phpstan analyse -c phpstan.neon -l max src/  
      ```

- Coding Standard

  ```bash
  vendor/bin/ecs check src
  ```

#### Opening Sylius with your plugin

- Using `test` environment:

    ```bash
    (cd tests/Application && APP_ENV=test bin/console sylius:fixtures:load)
    (cd tests/Application && APP_ENV=test bin/console server:run -d public)
    ```

- Using `dev` environment:

    ```bash
    (cd tests/Application && APP_ENV=dev bin/console sylius:fixtures:load)
    (cd tests/Application && APP_ENV=dev bin/console server:run -d public)
    ```

## License

This plugin is under the MIT license. See the complete license in the LICENSE file.

## Credits

Developed by [Webgriffe®](http://www.webgriffe.com/).
