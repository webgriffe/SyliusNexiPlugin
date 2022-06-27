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
use Psr\Log\LoggerInterface;
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
use Webgriffe\SyliusNexiPlugin\Decoder\RequestParamsDecoderInterface;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Api;
use Webmozart\Assert\Assert;

final class CaptureAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
{
    use GatewayAwareTrait, ApiAwareTrait;

    /** @var Api */
    protected $api;

    public function __construct(
        private Signer $signer,
        private Checker $checker,
        private RequestParamsDecoderInterface $decoder,
        private LoggerInterface $logger
    ) {
    }

    public function setApi($api): void
    {
        if (false === $api instanceof Api) {
            throw new UnsupportedApiException(sprintf('Not supported. Expected %s instance to be set as api.', Api::class));
        }

        $this->api = $api;
    }

    /**
     * This action handle 2 requests: the POST is the server2server from the payment gateway to sylius
     * and the GET is from the client browser to sylius.
     * The latter contains the information to handle the request from the client browser to the payment gateway
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $httpRequest = new GetHttpRequest();
        $this->gateway->execute($httpRequest);

        /** @var PaymentInterface $payment */
        $payment = $request->getFirstModel();

        $isS2S = false;
        /** @var array<string, string> $requestParams */
        $requestParams = $httpRequest->query;
        if (count($requestParams) === 0) {
            /** @var array<string, string> $requestParams */
            $requestParams = $httpRequest->request;
            $isS2S = true;
        }

        $requestParams = $this->decoder->decode($requestParams);
        $this->logger->debug('Nexi payment request parameters', ['parameter' => $requestParams, 'isS2S' => $isS2S]);

        if (isset($requestParams['esito'])) {
            /** @var ArrayObject $details */
            $details = $request->getModel();
            $this->logger->debug('Nexi payment request details', ['details' => $details, 'isS2S' => $isS2S]);

            if ($requestParams['esito'] === Result::OUTCOME_ANNULLO) {
                $this->logger->notice('Nexi payment status from http request is cancelled.');
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

            if ($isS2S) {
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
        $this->logger->debug('Nexi payment request prepared for the client browser', ['request' => $nexiRequest->getParams()]);

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
