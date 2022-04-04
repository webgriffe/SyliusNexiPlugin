<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action;

use GuzzleHttp\Psr7\ServerRequest;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpPostRedirect;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Security\TokenInterface;
use Sylius\Bundle\PayumBundle\Request\GetStatus;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Webgriffe\LibQuiPago\Lists\SignatureMethod;
use Webgriffe\LibQuiPago\Notification\Request as LibQuiPagoRequest;
use Webgriffe\LibQuiPago\Notification\Result;
use Webgriffe\LibQuiPago\PaymentInit\Request;
use Webgriffe\LibQuiPago\Signature\Checker;
use Webgriffe\LibQuiPago\Signature\Signer;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Api;
use Webmozart\Assert\Assert;

final class CaptureAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
{
    use GatewayAwareTrait, ApiAwareTrait;

    /** @var Api */
    protected $api;

    public function __construct(
        private Signer $signer,
        private Checker $checker
    ) {
    }

    public function setApi($api): void
    {
        if (false === $api instanceof Api) {
            throw new UnsupportedApiException(sprintf('Not supported. Expected %s instance to be set as api.', Api::class));
        }

        $this->api = $api;
    }

    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $httpRequest = new GetHttpRequest();
        $this->gateway->execute($httpRequest);

        /** @var PaymentInterface $payment */
        $payment = $request->getFirstModel();

        $isPost = false;
        $requestParams = $httpRequest->query;
        if (count($requestParams) === 0) {
            $requestParams = $httpRequest->request;
            $isPost = true;
        }

        if (isset($requestParams['esito'])) {
            /** @var ArrayObject $details */
            $details = $request->getModel();

            if ($requestParams['esito'] === Result::OUTCOME_ANNULLO) {
                $details->replace($requestParams);

                return;
            }

            $serverRequest = ServerRequest::fromGlobals();
            $this->checker->checkSignature(
                LibQuiPagoRequest::buildFromHttpRequest($serverRequest),
                $this->api->getMacKey(),
                SignatureMethod::SHA1_METHOD
            );
            $details->replace($requestParams);

            if ($isPost) {
                $this->gateway->execute(new GetStatus($payment));

                throw new HttpResponse('200');
            }

            return;
        }

        $order = $payment->getOrder();
        Assert::isInstanceOf($order, OrderInterface::class);

        $customer = $order->getCustomer();
        Assert::isInstanceOf($customer, CustomerInterface::class);

        $transactionCode = $order->getNumber() . '-' . $payment->getId();

        Assert::integer($payment->getAmount());

        $token = $request->getToken();
        Assert::isInstanceOf($token, TokenInterface::class);

        $nexiRequest = new Request(
            $this->api->getMerchantAlias(),
            $payment->getAmount() / 100,
            $transactionCode,
            $token->getTargetUrl(),
            $customer->getEmail(),
            $token->getTargetUrl(),
            null,
            $this->mapLocaleCodeToNexiLocaleCode($order->getLocaleCode()),
            // Notify url (server-to-server) can follow the same operations as user callback
            $token->getTargetUrl(),
            null,
            null,
            '#' . $order->getNumber()
        );

        $this->signer->sign($nexiRequest, $this->api->getMacKey(), SignatureMethod::SHA1_METHOD);

        throw new HttpPostRedirect(
            $this->api->getApiEndpoint(),
            $nexiRequest->getParams()
        );
    }

    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess;
    }

    private function mapLocaleCodeToNexiLocaleCode(?string $localeCode): string
    {
        return match (strtolower(substr((string) $localeCode, 0, 2))) {
            'it' => 'ITA',
            'es' => 'SPA',
            'fr' => 'FRA',
            'de' => 'GER',
            'ja' => 'JPN',
            'cn' => 'CHI',
            'ar' => 'ARA',
            'ru' => 'RUS',
            'pt' => 'POR',
            default => 'ENG',
        };
    }
}
