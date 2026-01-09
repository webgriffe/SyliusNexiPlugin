<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Payum\Nexi;

final class Api
{
    public const CODE = 'nexi';

    public const API_URL_TEST = 'https://int-ecommerce.nexi.it/ecomm/ecomm/DispatcherServlet';

    public const API_URL_LIVE = 'https://ecommerce.nexi.it/ecomm/ecomm/DispatcherServlet';

    public const RESULT_FIELD = 'esito';

    public function __construct(private array $config)
    {
    }

    public function getApiEndpoint(): string
    {
        return $this->config['sandbox'] ? self::API_URL_TEST : self::API_URL_LIVE;
    }

    public function getMerchantAlias(): string
    {
        return $this->config['alias'];
    }

    public function getMacKey(): string
    {
        return $this->config['mac_key'];
    }
}
