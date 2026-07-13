<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusNexiPlugin\Unit\Payum\Nexi\Action;

use Payum\Core\GatewayInterface;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\Cancel;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Sylius\Component\Core\Model\Payment;
use Webgriffe\LibQuiPago\Signature\Checker;
use Webgriffe\SyliusNexiPlugin\Decoder\RequestParamsDecoder;
use Webgriffe\SyliusNexiPlugin\Factory\GetHttpRequestFactory;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action\CancelAction;

final class CancelActionTest extends TestCase
{
    public function testRepliesWithBadRequestWhenOutcomeParameterIsMissing(): void
    {
        $action = new CancelAction(
            $this->createMock(Checker::class),
            new RequestParamsDecoder(),
            new NullLogger(),
            new GetHttpRequestFactory(),
        );
        $action->setGateway($this->createMock(GatewayInterface::class));

        try {
            $action->execute(new Cancel(new Payment()));
            self::fail('Expected an HttpResponse reply to be thrown.');
        } catch (HttpResponse $reply) {
            self::assertSame(400, $reply->getStatusCode());
        }
    }
}
