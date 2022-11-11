<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusNexiPlugin\Behat\Service\Mocker;

use Mockery\Mock;
use Payum\Core\HttpClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Sylius\Behat\Service\Mocker\MockerInterface;
use Sylius\Behat\Service\ResponseLoaderInterface;

final class NexiApiMocker
{
    public function __construct(
        private MockerInterface $mocker,
        private ResponseLoaderInterface $responseLoader
    ) {
    }

    public function performActionInApiInitializeScope(callable $action): void
    {
        $this->mockApiPaymentInitializeResponse();
        $action();
        $this->mocker->unmockAll();
    }

    private function mockApiPaymentInitializeResponse(): void
    {
        $mockedResponse = $this->responseLoader->getMockedResponse('Nexi/nexi_initialize_payment.json');
        $setExpressCheckoutStream = $this->mockStream($mockedResponse['setExpressCheckout']);
        $setExpressCheckoutResponse = $this->mockHttpResponse(200, $setExpressCheckoutStream);

        $getExpressCheckoutDetailsStream = $this->mockStream($mockedResponse['getExpressCheckoutDetails']);
        $getExpressCheckoutDetailsResponse = $this->mockHttpResponse(200, $getExpressCheckoutDetailsStream);

        $this->mocker->mockService('sylius.payum.http_client', HttpClientInterface::class)
            ->shouldReceive('send')
            ->twice()
            ->andReturn($setExpressCheckoutResponse, $getExpressCheckoutDetailsResponse)
        ;
    }

    private function mockStream(string $content): Mock
    {
        $mockedStream = $this->mocker->mockCollaborator(StreamInterface::class);
        $mockedStream->shouldReceive('getContents')->once()->andReturn($content);
        $mockedStream->shouldReceive('close')->once()->andReturn();

        return $mockedStream;
    }

    private function mockHttpResponse(int $statusCode, $streamMock): Mock
    {
        $mockedHttpResponse = $this->mocker->mockCollaborator(ResponseInterface::class);
        $mockedHttpResponse->shouldReceive('getStatusCode')->once()->andReturn($statusCode);
        $mockedHttpResponse->shouldReceive('getBody')->once()->andReturn($streamMock);

        return $mockedHttpResponse;
    }
}
