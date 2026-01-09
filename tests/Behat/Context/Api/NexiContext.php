<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusNexiPlugin\Behat\Context\Api;

use Behat\Behat\Context\Context;
use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use Sylius\Bundle\PayumBundle\Model\PaymentSecurityTokenInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Tests\Webgriffe\SyliusNexiPlugin\Behat\Context\PayumPaymentTrait;
use Tests\Webgriffe\SyliusNexiPlugin\Behat\Context\Setup\PaymentContext;
use Webgriffe\LibQuiPago\Lists\Currency;
use Webgriffe\LibQuiPago\Lists\SignatureMethod;
use Webgriffe\LibQuiPago\Notification\Result;
use Webgriffe\LibQuiPago\Signature\DefaultSignatureHashingManager;
use Webgriffe\SyliusKlarnaPaymentsPlugin\Client\Enum\HostedPaymentPageSessionStatus;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Api;

final class NexiContext implements Context
{
    use PayumPaymentTrait;

    /**
     * @param RepositoryInterface<PaymentSecurityTokenInterface> $paymentTokenRepository
     * @param PaymentRepositoryInterface<PaymentInterface> $paymentRepository
     */
    public function __construct(
        private readonly RepositoryInterface $paymentTokenRepository,
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly GuzzleClientInterface|ClientInterface $client,
    ) {
        // TODO: Why config parameters are not loaded?
        $this->urlGenerator->setContext(new RequestContext('', 'GET', '127.0.0.1:8080', 'https'));
    }


    /**
     * @When Nexi notify the store about the successful payment
     */
    public function nexiNotifyTheStoreAboutTheSuccessfulPayment(): void
    {
        $payment = $this->getCurrentPayment();
        [$paymentCaptureSecurityToken, $paymentNotifySecurityToken] = $this->getCurrentPaymentSecurityTokens($payment);

        $successResponsePayload = $this->getSuccessResponsePayload($payment);
        $this->notifyPaymentState($paymentNotifySecurityToken, $successResponsePayload);
    }

    /**
     * @When /^Nexi notify the store about the failed payment$/
     */
    public function nexiNotifyTheStoreAboutTheFailedPayment(): void
    {
        $payment = $this->getCurrentPayment();
        [$paymentCaptureSecurityToken, $paymentNotifySecurityToken] = $this->getCurrentPaymentSecurityTokens($payment);

        $failureResponsePayload = $this->getFailureResponsePayload($payment);
        $this->notifyPaymentState($paymentNotifySecurityToken, $failureResponsePayload);
    }

    /**
     * @When /^Nexi notify the store about the cancelled payment$/
     */
    public function nexiNotifyTheStoreAboutTheCancelledPayment(): void
    {
        $payment = $this->getCurrentPayment();
        [$paymentCaptureSecurityToken, $paymentNotifySecurityToken] = $this->getCurrentPaymentSecurityTokens($payment);

        $cancelResponsePayload = $this->getCancelResponsePayload($payment);
        $this->notifyPaymentState($paymentNotifySecurityToken, $cancelResponsePayload);
    }

    /**
     * @return PaymentRepositoryInterface<PaymentInterface>
     */
    protected function getPaymentRepository(): PaymentRepositoryInterface
    {
        return $this->paymentRepository;
    }

    /**
     * @return RepositoryInterface<PaymentSecurityTokenInterface>
     */
    protected function getPaymentTokenRepository(): RepositoryInterface
    {
        return $this->paymentTokenRepository;
    }

    private function notifyPaymentState(PaymentSecurityTokenInterface $token, array $responsePayload): void
    {
        $formParams = http_build_query($responsePayload);
        $request = new Request(
            'POST',
            $this->getNotifyUrl($token),
            [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            $formParams,
        );
        if ($this->client instanceof GuzzleClientInterface) {
            $this->client->send($request);

            return;
        }
        $this->client->sendRequest($request);
    }

    private function getNotifyUrl(PaymentSecurityTokenInterface $token): string
    {
        return $this->urlGenerator->generate(
            'payum_notify_do',
            ['payum_token' => $token->getHash()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
    }

    private function getPaymentCode(PaymentInterface $payment): string
    {
        return $payment->getOrder()?->getNumber() . '-' . $payment->getId();
    }

    private function getResponseMac(PaymentInterface $payment, string $result, string $date, string $time, string $authCode): string
    {
        $macString = sprintf(
            'codTrans=%sesito=%simporto=%sdivisa=%sdata=%sorario=%scodAut=%s%s',
            $this->getPaymentCode($payment),
            $result,
            $payment->getAmount(),
            Currency::EURO_CURRENCY_CODE,
            $date,
            $time,
            $authCode,
            PaymentContext::NEXI_MAC_KEY,
        );

        return (new DefaultSignatureHashingManager())->hashSignatureString($macString, SignatureMethod::SHA1_METHOD);
    }

    private function getSuccessResponsePayload(PaymentInterface $payment): array
    {
        $date = date('Ymd');
        $time = date('Hmi');

        return [
            Api::RESULT_FIELD => Result::OUTCOME_OK,
            'messaggio' => 'Transazione autorizzata.',
            'alias' => PaymentContext::NEXI_ALIAS,
            'importo' => (string) $payment->getAmount(),
            'divisa' => Currency::EURO_CURRENCY_CODE,
            'codTrans' => $this->getPaymentCode($payment),
            'mac' => $this->getResponseMac($payment, Result::OUTCOME_OK, $date, $time, 'OKAY'),
            'data' => $date,
            'orario' => $time,
            'codAut' => 'OKAY'
        ];
    }

    private function getFailureResponsePayload(PaymentInterface $payment): array
    {
        $date = date('Ymd');
        $time = date('Hmi');

        return [
            Api::RESULT_FIELD => Result::OUTCOME_KO,
            'messaggio' => 'Transazione non autorizzata.',
            'alias' => PaymentContext::NEXI_ALIAS,
            'importo' => (string) $payment->getAmount(),
            'divisa' => Currency::EURO_CURRENCY_CODE,
            'codTrans' => $this->getPaymentCode($payment),
            'mac' => $this->getResponseMac($payment, Result::OUTCOME_KO, $date, $time, 'OKAY'),
            'data' => $date,
            'orario' => $time,
            'codAut' => 'OKAY'
        ];
    }

    private function getCancelResponsePayload(PaymentInterface $payment): array
    {
        $date = date('Ymd');
        $time = date('Hmi');

        return [
            Api::RESULT_FIELD => Result::OUTCOME_ANNULLO,
            'alias' => PaymentContext::NEXI_ALIAS,
            'importo' => (string) $payment->getAmount(),
            'divisa' => Currency::EURO_CURRENCY_CODE,
            'codTrans' => $this->getPaymentCode($payment),
        ];
    }
}
