<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusNexiPlugin\Decoder;

use PhpSpec\ObjectBehavior;
use Webgriffe\SyliusNexiPlugin\Decoder\RequestParamsDecoder;
use Webgriffe\SyliusNexiPlugin\Decoder\RequestParamsDecoderInterface;

final class RequestParamsDecoderSpec extends ObjectBehavior
{
    public function it_is_initializable(): void
    {
        $this->shouldHaveType(RequestParamsDecoder::class);
    }

    public function it_implements_request_params_decoder_interface(): void
    {
        $this->shouldHaveType(RequestParamsDecoderInterface::class);
    }

    public function it_decodes_non_utf8_characters(): void
    {
        $this->decode([
            'nome' => mb_convert_encoding('Fábien', 'ISO-8859-1', 'UTF-8'),
            'cognome' => mb_convert_encoding('Potencier', 'ISO-8859-1', 'UTF-8'),
            'data' => '20220530',
            'descrizione' => '#000000068',
            'divisa' => mb_convert_encoding('EUR', 'ISO-8859-1', 'UTF-8'),
            'esito' => 'OK',
        ])->shouldReturn([
            'nome' => 'Fábien',
            'cognome' => 'Potencier',
            'data' => '20220530',
            'descrizione' => '#000000068',
            'divisa' => 'EUR',
            'esito' => 'OK',
        ]);
    }
}
