<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Decoder;

use const PHP_VERSION_ID;
use RuntimeException;

final class RequestParamsDecoder implements RequestParamsDecoderInterface
{
    public function decode(array $requestParams): array
    {
        foreach (array_keys($requestParams) as $key) {
            $requestParams[$key] = self::detectAndCleanUtf8($requestParams[$key]);
        }

        return $requestParams;
    }

    /**
     * @credits to Seldaek/monolog https://github.com/Seldaek/monolog/blob/60ad5183b5e5d6c9d4047e9f3072d36071dcc161/src/Monolog/Utils.php#L146
     * Detect invalid UTF-8 string characters and convert to valid UTF-8.
     *
     * Valid UTF-8 input will be left unmodified, but strings containing
     * invalid UTF-8 codepoints will be reencoded as UTF-8 with an assumed
     * original encoding of ISO-8859-1. This conversion may result in
     * incorrect output if the actual encoding was not ISO-8859-1, but it
     * will be clean UTF-8 output and will not rely on expensive and fragile
     * detection algorithms.
     *
     * Function converts the input in place in the passed variable so that it
     * can be used as a callback for array_walk_recursive.
     *
     * @param string $data Input to check and convert if needed, passed by ref
     */
    private static function detectAndCleanUtf8(string $data): string
    {
        if (preg_match('//u', $data) !== 1) {
            $data = preg_replace_callback(
                '/[\x80-\xFF]+/',
                /** @param string[] $m */
                static function (array $m): string {
                    return mb_convert_encoding($m[0], 'UTF-8', 'ISO-8859-1');
                },
                $data,
            );
            if (!is_string($data)) {
                $pcreErrorCode = preg_last_error();

                throw new RuntimeException('Failed to preg_replace_callback: ' . $pcreErrorCode . ' / ' . self::pcreLastErrorMessage($pcreErrorCode));
            }
            $data = str_replace(
                ['¤', '¦', '¨', '´', '¸', '¼', '½', '¾'],
                ['€', 'Š', 'š', 'Ž', 'ž', 'Œ', 'œ', 'Ÿ'],
                $data,
            );
        }

        return $data;
    }

    public static function pcreLastErrorMessage(int $code): string
    {
        if (PHP_VERSION_ID >= 80000) {
            return preg_last_error_msg();
        }

        /** @var array<string, int> $constants */
        $constants = (get_defined_constants(true))['pcre'];
        $constants = array_filter($constants, static function (string $key): bool {
            return str_ends_with($key, '_ERROR');
        }, \ARRAY_FILTER_USE_KEY);

        $constants = array_flip($constants);

        return $constants[$code] ?? 'UNDEFINED_ERROR';
    }
}
