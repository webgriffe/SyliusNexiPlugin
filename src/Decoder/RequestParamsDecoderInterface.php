<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Decoder;

interface RequestParamsDecoderInterface
{
    /** @param array<string, string> $requestParams */
    public function decode(array $requestParams): array;
}
