<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Payum\Nexi;

final class Api
{
    public const API_URL_TEST = 'https://int-ecommerce.nexi.it/ecomm/ecomm/DispatcherServlet';

    public const API_URL_LIVE = 'https://ecommerce.nexi.it/ecomm/ecomm/DispatcherServlet';

    /** @var array */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
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
