<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\GetToken;
use Payum\Core\Request\Notify;
use Psr\Log\LoggerInterface;

final class NotifyNullAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * @param $request Notify
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        // This is needed to populate the http request with GET and POST params from current request
        $this->gateway->execute($httpRequest = new GetHttpRequest());

        $this->logger->debug('Nexi notify null action request.', ['queryParameters' => $httpRequest->query]);

        //we are back from Nexi site, so we have to just update model.
        if (empty($httpRequest->query['notify_token'])) {
            throw new HttpResponse('Missing notify_token in Nexi notify request', 400);
        }

        // Resolve the token
        $this->gateway->execute($token = new GetToken($httpRequest->query['notify_token']));

        // Execute the capture with the resolved token (the NotifyAction will be called)
        $this->gateway->execute(new Notify($token->getToken()));
    }

    public function supports($request): bool
    {
        return
            $request instanceof Notify &&
            null === $request->getModel();
    }
}
