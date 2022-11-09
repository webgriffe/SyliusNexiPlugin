<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Factory;

use Payum\Core\Request\GetHttpRequest;

interface GetHttpRequestFactoryInterface
{
    public function create(): GetHttpRequest;
}
