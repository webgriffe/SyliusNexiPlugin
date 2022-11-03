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
     * {@inheritDoc}
     *
     * @param $request Notify
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $this->gateway->execute($httpRequest = new GetHttpRequest());

        $this->logger->debug('Nexi notify null action request.', ['parameters' => $httpRequest->query]);

        //we are back from be2bill site so we have to just update model.
        if (empty($httpRequest->query['notify_token'])) {
            throw new HttpResponse('Missing notify_token in Nexi notify request', 400);
        }

        $this->gateway->execute($getToken = new GetToken($httpRequest->query['notify_token']));
        $this->gateway->execute(new Notify($getToken->getToken()));
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Notify &&
            null === $request->getModel()
            ;
    }
}
