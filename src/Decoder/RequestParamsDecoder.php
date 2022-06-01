<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Decoder;

final class RequestParamsDecoder implements RequestParamsDecoderInterface
{
    public function decode(array $requestParams): array
    {
        foreach (array_keys($requestParams) as $key) {
            $requestParams[$key] = mb_convert_encoding($requestParams[$key], 'UTF-8', 'ISO-8859-1');
        }

        return $requestParams;
    }
}
