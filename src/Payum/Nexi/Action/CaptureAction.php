<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Payum\Nexi\Action;

use GuzzleHttp\Psr7\ServerRequest;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpPostRedirect;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Security\TokenInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
        private LoggerInterface $logger,
        private PaymentRepositoryInterface $paymentRepository,
        private UrlGeneratorInterface $urlGenerator,
    ) {
        $this->apiClass = Api::class;
    }

    /**
     * This action is invoked by two main entries: the starting payment procedure and the return back to the store after
     * a completed, cancelled or failed checkout on Nexi.
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        // This is needed to populate the http request with GET and POST params from current request
        $this->gateway->execute($httpRequest = new GetHttpRequest());

        /** @var SyliusPaymentInterface $payment */
        $payment = $request->getModel();
        Assert::isInstanceOf($payment, SyliusPaymentInterface::class);
        if (array_key_exists('esito', $payment->getDetails())) {
            // Already captured this payment
            return;
        }

        $order = $payment->getOrder();
        Assert::isInstanceOf($order, OrderInterface::class);

        /** @var array<string, string> $parameters */
        $parameters = $httpRequest->query;
        if (count($parameters) > 0) {
            // It is the request coming back from Nexi after the checkout

            // Decode non UTF-8 characters
            $parameters = $this->decoder->decode($parameters);
            $this->logger->debug('Nexi payment query parameters', ['parameters' => $parameters]);

            Assert::keyExists($parameters, 'esito', sprintf('The key "%s" does not exists in the parameters coming back from Nexi, let\'s check the documentation [%s] if something has changed!', 'esito', 'https://ecommerce.nexi.it/specifiche-tecniche/codicebase/introduzione.html'));

            $result = $parameters['esito'];
            if ($result === Result::OUTCOME_ANNULLO) {
                $this->logger->notice(sprintf(
                    'Nexi payment status returned for payment with id "%s" from order with id "%s" is cancelled.',
                    $payment->getId(),
                    $order->getId()
                ));
                $payment->setDetails($parameters);

                return;
            }

            $serverRequest = ServerRequest::fromGlobals();
            $this->checker->checkSignature(
                LibQuiPagoRequest::buildFromHttpRequest($serverRequest),
                $this->api->getMacKey(),
                SignatureMethod::SHA1_METHOD
            );
            $payment->setDetails($parameters);

            return;
        }

        $customer = $order->getCustomer();
        Assert::isInstanceOf($customer, CustomerInterface::class);

        $transactionCode = $order->getNumber() . '-' . $payment->getId();

        $amount = $payment->getAmount();
        Assert::integer($amount);

        /** @var TokenInterface $token */
        $token = $request->getToken();
        Assert::isInstanceOf($token, TokenInterface::class);

        $nexiRequest = new Request(
            $this->api->getMerchantAlias(),
            $amount / 100,
            $transactionCode,
            $token->getTargetUrl(),
            $customer->getEmail(),
            $token->getTargetUrl(),
            null,
            $this->mapLocaleCodeToNexiLocaleCode($order->getLocaleCode()),
            $this->urlGenerator->generate(
                'payum_notify_do_unsafe',
                ['gateway' => 'nexi', 'notify_token' => $token->getHash()],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
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

    public function supports($request): bool
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof SyliusPaymentInterface;
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
