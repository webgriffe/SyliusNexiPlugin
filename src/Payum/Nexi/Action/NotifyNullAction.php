<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\Generic;
use Payum\Core\Request\GetToken;
use Payum\Core\Request\Notify;
use Psr\Log\LoggerInterface;
use Webgriffe\SyliusNexiPlugin\Factory\GetHttpRequestFactoryInterface;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class NotifyNullAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    public function __construct(
        private LoggerInterface $logger,
        private GetHttpRequestFactoryInterface $getHttpRequestFactory,
    ) {
    }

    /**
     * This action is invoked by Nexi with the Server2Server POST notify. We have to use the route
     * notify unsafe because, otherwise, the notify url and the target url must be the same routes.
     * The purpose of this action is to retrieve the payment token from the hash of the payment in
     * the query parameter. See Payum\Be2Bill\Action\NotifyNullAction to have a look to another example.
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     *
     * @phpstan-ignore-next-line
     *
     * @param Notify&Generic $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        // This is needed to populate the http request with GET and POST params from current request
        $this->gateway->execute($httpRequest = $this->getHttpRequestFactory->create());

        $this->logger->debug('Nexi notify null action request.', ['queryParameters' => $httpRequest->query]);

        //we are back from Nexi site, so we have to just update model.
        if (!array_key_exists('notify_token', $httpRequest->query) || $httpRequest->query['notify_token'] === '' || $httpRequest->query['notify_token'] === null) {
            throw new HttpResponse('Missing notify_token in Nexi notify request', 400);
        }

        // Resolve the token
        $this->gateway->execute($token = new GetToken((string) $httpRequest->query['notify_token']));

        // Execute the payment parameters capture with the resolved token (the NotifyAction will be called)
        $this->gateway->execute(new Notify($token->getToken()));
    }

    public function supports($request): bool
    {
        return
            $request instanceof Notify &&
            null === $request->getModel();
    }
}
