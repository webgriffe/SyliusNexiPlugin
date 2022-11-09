<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusNexiPlugin\Factory;

use Payum\Core\Request\GetHttpRequest;
use Webgriffe\SyliusNexiPlugin\Factory\GetHttpRequestFactory;
use PhpSpec\ObjectBehavior;
use Webgriffe\SyliusNexiPlugin\Factory\GetHttpRequestFactoryInterface;

class GetHttpRequestFactorySpec extends ObjectBehavior
{
    public function let(): void
    {
        $this->beConstructedWith();
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(GetHttpRequestFactory::class);
    }

    public function it_implements_get_http_request_factory_interface(): void
    {
        $this->shouldHaveType(GetHttpRequestFactoryInterface::class);
    }

    public function it_creates_get_http_request(): void
    {
        $this->create()->shouldReturnAnInstanceOf(GetHttpRequest::class);
    }
}
