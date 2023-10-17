<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusNexiPlugin\Behat\Context\Ui;

use Behat\Behat\Context\Context;
use Behat\Mink\Session;
use GuzzleHttp\ClientInterface;
use JsonException;
use Sylius\Behat\Page\Shop\Order\ShowPageInterface;
use Sylius\Bundle\PayumBundle\Model\PaymentSecurityTokenInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Tests\Webgriffe\SyliusNexiPlugin\Behat\Context\Setup\PaymentContext;
use Tests\Webgriffe\SyliusNexiPlugin\Behat\Page\Shop\Payum\Capture\PayumCaptureDoPageInterface;
use Webgriffe\LibQuiPago\Lists\Currency;
use Webgriffe\LibQuiPago\Lists\SignatureMethod;
use Webgriffe\LibQuiPago\Notification\Result;
use Webgriffe\LibQuiPago\Signature\DefaultSignatureHashingManager;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Api;
use Webmozart\Assert\Assert;

final class NexiContext implements Context
{
    public function __construct(
        private PayumCaptureDoPageInterface $payumCaptureDoPage,
        private RepositoryInterface $paymentTokenRepository,
        private PaymentRepositoryInterface $paymentRepository,
        private UrlGeneratorInterface $urlGenerator,
        private ClientInterface $client,
        private Session $session,
        private ShowPageInterface $orderDetails,
    ) {
        // TODO: Why config parameters are not loaded?
        $this->urlGenerator->setContext(new RequestContext('', 'GET', '127.0.0.1:8080', 'https'));
    }

    /**
     * @When /^I complete the payment on Nexi$/
     *
     * @throws JsonException
     */
    public function iCompleteThePaymentOnNexi(): void
    {
        $payment = $this->getCurrentPayment();
        [$paymentCaptureSecurityToken, $paymentNotifySecurityToken] = $this->getCurrentCaptureAndNotifyPaymentSecurityTokens($payment);

        $this->checkIfAllDataToSendToNexiAreOk($paymentCaptureSecurityToken, $paymentNotifySecurityToken, $payment);

        $successResponsePayload = $this->getSuccessResponsePayload($payment);

        $this->simulateS2SPaymentNotify($paymentNotifySecurityToken, $successResponsePayload);

        // Simulate coming back from Nexi after completed checkout
        $this->session->getDriver()->visit($paymentCaptureSecurityToken->getTargetUrl() . '?' . http_build_query($successResponsePayload));
    }

    /**
     * @Given /^I have cancelled (?:|my )Nexi payment$/
     * @When /^I cancel (?:|my )Nexi payment$/
     *
     * @throws JsonException
     */
    public function iCancelMyNexiPayment(): void
    {
        $payment = $this->getCurrentPayment();
        [$paymentCaptureSecurityToken, $paymentNotifySecurityToken] = $this->getCurrentCaptureAndNotifyPaymentSecurityTokens($payment);

        $this->checkIfAllDataToSendToNexiAreOk($paymentCaptureSecurityToken, $paymentNotifySecurityToken, $payment);

        $cancelResponsePayload = [
            Api::RESULT_FIELD => Result::OUTCOME_ANNULLO,
            'alias' => PaymentContext::NEXI_ALIAS,
            'importo' => (string) $payment->getAmount(),
            'divisa' => Currency::EURO_CURRENCY_CODE,
            'codTrans' => $this->getPaymentCode($payment),
        ];

        // Simulate S2S payment notify
        $this->simulateS2SPaymentNotify($paymentNotifySecurityToken, $cancelResponsePayload);

        // Simulate coming back from Nexi after completed checkout
        $this->session->getDriver()->visit($paymentCaptureSecurityToken->getTargetUrl() . '?' . http_build_query($cancelResponsePayload));
    }

    /**
     * @When /^I try to complete pay again with Nexi$/
     */
    public function iTryToCompletePayAgainWithNexi(): void
    {
        $this->orderDetails->pay();
        $this->iCompleteThePaymentOnNexi();
    }

    /**
     * @When /^I try to cancel the payment again with Nexi$/
     */
    public function iTryToCancelThePaymentAgainWithNexi(): void
    {
        $this->orderDetails->pay();
        $this->iCancelMyNexiPayment();
    }

    /**
     * @Given /^I complete the payment on Nexi without returning to the store$/
     */
    public function iCompleteThePaymentOnNexiWithoutReturningToTheStore(): void
    {
        $payment = $this->getCurrentPayment();
        [$paymentCaptureSecurityToken, $paymentNotifySecurityToken] = $this->getCurrentCaptureAndNotifyPaymentSecurityTokens($payment);

        $this->checkIfAllDataToSendToNexiAreOk($paymentCaptureSecurityToken, $paymentNotifySecurityToken, $payment);

        $successResponsePayload = $this->getSuccessResponsePayload($payment);

        $this->simulateS2SPaymentNotify($paymentNotifySecurityToken, $successResponsePayload);
    }

    /**
     * @return array{PaymentSecurityTokenInterface, PaymentSecurityTokenInterface}
     */
    private function getCurrentCaptureAndNotifyPaymentSecurityTokens(PaymentInterface $payment): array
    {
        /** @var PaymentSecurityTokenInterface[] $paymentSecurityTokens */
        $paymentSecurityTokens = array_filter($this->paymentTokenRepository->findAll(), static function (PaymentSecurityTokenInterface $token) use ($payment): bool {
            return $token->getDetails()->getId() === $payment->getId() && $token->getDetails()->getClass() === get_class($payment);
        });
        Assert::count($paymentSecurityTokens, 3, sprintf('Expected 3 payment security tokens, got %s.', count($paymentSecurityTokens)));

        $paymentCaptureSecurityToken = $this->extractCaptureSecurityToken($paymentSecurityTokens);
        $paymentNotifySecurityToken = $this->extractNotifySecurityToken($paymentSecurityTokens);

        return [$paymentCaptureSecurityToken, $paymentNotifySecurityToken];
    }

    private function getCurrentPayment(): PaymentInterface
    {
        /** @var PaymentInterface[] $payments */
        $payments = $this->paymentRepository->findBy(['state' => PaymentInterface::STATE_NEW]);
        $payment = reset($payments);
        Assert::isInstanceOf($payment, PaymentInterface::class);

        return $payment;
    }

    private function assertPageHasValidPaymentDetails(
        PaymentInterface $payment,
        PaymentSecurityTokenInterface $captureToken,
        PaymentSecurityTokenInterface $notifyToken,
    ): void {
        Assert::eq(
            $this->payumCaptureDoPage->getAlias(),
            PaymentContext::NEXI_ALIAS,
            'The data to send to Nexi are not valid! Expected an alias equal to %2$s. Got: %s'
        );
        Assert::eq(
            $this->payumCaptureDoPage->getAmount(),
            $payment->getAmount(),
            'The data to send to Nexi are not valid! Expected an amount equal to %2$s. Got: %s'
        );
        Assert::eq(
            $this->payumCaptureDoPage->getCurrency(),
            Currency::EURO_CURRENCY_CODE,
            'The data to send to Nexi are not valid! Expected a currency equal to %2$s. Got: %s'
        );
        Assert::eq(
            $this->payumCaptureDoPage->getPaymentCode(),
            $this->getPaymentCode($payment),
            'The data to send to Nexi are not valid! Expected a payment code equal to %2$s. Got: %s'
        );
        Assert::eq(
            $this->payumCaptureDoPage->getSuccessUrl(),
            $this->getCaptureUrl($captureToken),
            'The data to send to Nexi are not valid! Expected a success url equal to %2$s. Got: %s'
        );
        Assert::eq(
            $this->payumCaptureDoPage->getBackUrl(),
            $this->getCaptureUrl($captureToken),
            'The data to send to Nexi are not valid! Expected a back url equal to %2$s. Got: %s'
        );
        Assert::eq(
            $this->payumCaptureDoPage->getPostUrl(),
            $this->getNotifyUrl($notifyToken),
            'The data to send to Nexi are not valid! Expected a post url equal to %2$s. Got: %s'
        );
        Assert::eq(
            $this->payumCaptureDoPage->getEmail(),
            $payment->getOrder()->getCustomer()->getEmail(),
            'The data to send to Nexi are not valid! Expected an email equal to %2$s. Got: %s'
        );
        Assert::eq(
            $this->payumCaptureDoPage->getLanguageId(),
            'ENG',
            'The data to send to Nexi are not valid! Expected a language id equal to %2$s. Got: %s'
        );
        Assert::eq(
            $this->payumCaptureDoPage->getDescription(),
            '#' . $payment->getOrder()->getNumber(),
            'The data to send to Nexi are not valid! Expected a description equal to %2$s. Got: %s'
        );
        Assert::eq(
            $this->payumCaptureDoPage->getMac(),
            $this->getMac($payment),
            'The data to send to Nexi are not valid! Expected a mac equal to %2$s. Got: %s'
        );
    }

    private function getMac(PaymentInterface $payment): string
    {
        $macString = sprintf(
            'codTrans=%sdivisa=%simporto=%s%s',
            $this->getPaymentCode($payment),
            Currency::EURO_CURRENCY_CODE,
            $payment->getAmount(),
            PaymentContext::NEXI_MAC_KEY,
        );

        return (new DefaultSignatureHashingManager())->hashSignatureString($macString, SignatureMethod::SHA1_METHOD);
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

    private function getPaymentCode(PaymentInterface $payment): string
    {
        return (string)$payment->getOrder()->getNumber() . '-' . (string)$payment->getId();
    }

    private function getNotifyUrl(PaymentSecurityTokenInterface $token): string
    {
        return $this->urlGenerator->generate(
            'payum_notify_do',
            ['payum_token' => $token->getHash()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    private function getCaptureUrl(PaymentSecurityTokenInterface $token): string
    {
        return $this->urlGenerator->generate(
            'payum_capture_do',
            ['payum_token' => $token->getHash()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    private function checkIfAllDataToSendToNexiAreOk(
        PaymentSecurityTokenInterface $paymentCaptureSecurityToken,
        PaymentSecurityTokenInterface $paymentNotifySecurityToken,
        PaymentInterface $payment,
    ): void {
        Assert::true($this->payumCaptureDoPage->isOpen(['payum_token' => $paymentCaptureSecurityToken->getHash()]), 'The current page is not the capture page.');
        $this->assertPageHasValidPaymentDetails($payment, $paymentCaptureSecurityToken, $paymentNotifySecurityToken);
        Assert::eq($paymentCaptureSecurityToken->getTargetUrl(), $this->getCaptureUrl($paymentCaptureSecurityToken));
    }

    private function getSuccessResponsePayload(PaymentInterface $payment): array
    {
        $date = date('Ymd');
        $time = date('Hmi');

        return [
            Api::RESULT_FIELD => Result::OUTCOME_OK,
            'messaggio' => 'Transazione autorizzata.',
            'alias' => PaymentContext::NEXI_ALIAS,
            'importo' => (string)$payment->getAmount(),
            'divisa' => Currency::EURO_CURRENCY_CODE,
            'codTrans' => $this->getPaymentCode($payment),
            'mac' => $this->getResponseMac($payment, Result::OUTCOME_OK, $date, $time, 'OKAY'),
            'data' => $date,
            'orario' => $time,
            'codAut' => 'OKAY'
        ];
    }

    private function simulateS2SPaymentNotify(PaymentSecurityTokenInterface $token, array $responsePayload): void
    {
        $this->client->request(
            'POST',
            $this->getNotifyUrl($token),
            ['form_params' => $responsePayload],
        );
    }

    /**
     * @param PaymentSecurityTokenInterface[] $paymentSecurityTokens
     */
    private function extractCaptureSecurityToken(array $paymentSecurityTokens): PaymentSecurityTokenInterface
    {
        /** @var PaymentSecurityTokenInterface $paymentSecurityToken */
        $paymentCaptureSecurityTokens = array_filter($paymentSecurityTokens, static function (PaymentSecurityTokenInterface $token): bool {
            return str_contains($token->getTargetUrl(), 'payment/capture');
        });
        Assert::count($paymentCaptureSecurityTokens, 1, sprintf('Expected 1 payment capture security token, got %s.', count($paymentCaptureSecurityTokens)));
        $paymentCaptureSecurityToken = array_pop($paymentCaptureSecurityTokens);
        Assert::isInstanceOf($paymentCaptureSecurityToken, PaymentSecurityTokenInterface::class);

        return $paymentCaptureSecurityToken;
    }

    /**
     * @param PaymentSecurityTokenInterface[] $paymentSecurityTokens
     */
    private function extractNotifySecurityToken(array $paymentSecurityTokens): PaymentSecurityTokenInterface
    {
        /** @var PaymentSecurityTokenInterface $paymentSecurityToken */
        $paymentNotifySecurityTokens = array_filter($paymentSecurityTokens, static function (PaymentSecurityTokenInterface $token): bool {
            return str_contains($token->getTargetUrl(), 'payment/notify');
        });
        Assert::count($paymentNotifySecurityTokens, 1, sprintf('Expected 1 payment notify security token, got %s.', count($paymentNotifySecurityTokens)));
        $paymentNotifySecurityToken = array_pop($paymentNotifySecurityTokens);
        Assert::isInstanceOf($paymentNotifySecurityToken, PaymentSecurityTokenInterface::class);

        return $paymentNotifySecurityToken;
    }
}
