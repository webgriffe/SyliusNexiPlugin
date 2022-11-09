<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Factory;

use Payum\Core\Request\GetHttpRequest;

final class GetHttpRequestFactory implements GetHttpRequestFactoryInterface
{
    public function create(): GetHttpRequest
    {
        return new GetHttpRequest();
    }
}
