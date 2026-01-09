<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusNexiPlugin\Behat\Context\Ui;

use Behat\Behat\Context\Context;
use Behat\Mink\Session;
use JsonException;
use Sylius\Behat\Page\Shop\Order\ShowPageInterface;
use Sylius\Behat\Page\Shop\Order\ThankYouPageInterface;
use Sylius\Bundle\PayumBundle\Model\PaymentSecurityTokenInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Tests\Webgriffe\SyliusNexiPlugin\Behat\Context\Setup\PaymentContext;
use Tests\Webgriffe\SyliusNexiPlugin\Behat\Page\Shop\Payment\ProcessPageInterface;
use Tests\Webgriffe\SyliusNexiPlugin\Behat\Page\Shop\Payum\Capture\PayumCaptureDoPageInterface;
use Webgriffe\LibQuiPago\Lists\Currency;
use Webgriffe\LibQuiPago\Lists\SignatureMethod;
use Webgriffe\LibQuiPago\Notification\Result;
use Webgriffe\LibQuiPago\Signature\DefaultSignatureHashingManager;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Api;
use Webmozart\Assert\Assert;

final class NexiContext implements Context
{
    /**
     * @param RepositoryInterface<PaymentSecurityTokenInterface> $paymentTokenRepository
     * @param PaymentRepositoryInterface<PaymentInterface> $paymentRepository
     * @param OrderRepositoryInterface<OrderInterface> $orderRepository
     */
    public function __construct(
        private readonly PayumCaptureDoPageInterface $payumCaptureDoPage,
        private readonly RepositoryInterface $paymentTokenRepository,
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Session $session,
        private readonly ShowPageInterface $orderDetails,
        private readonly ProcessPageInterface $paymentProcessPage,
        private readonly ThankYouPageInterface $thankYouPage,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly ShowPageInterface $orderShowPage,
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
        [$paymentCaptureSecurityToken] = $this->getCurrentCaptureAndNotifyPaymentSecurityTokens($payment);
        $successResponsePayload = $this->getSuccessResponsePayload($payment);

        // Simulate coming back from Nexi after completed checkout
        // Even if not read, we leave the Nexi parameters in the URL to simulate a real coming back from Nexi
        $this->session->getDriver()->visit($paymentCaptureSecurityToken->getTargetUrl() . '?' . http_build_query($successResponsePayload));
    }

    /**
     * @Then /^I should see be successfully redirected to Nexi payment gateway$/
     */
    public function iShouldSeeBeSuccessfullyRedirectedToNexiPaymentGateway(): void
    {
        $payment = $this->getCurrentPayment();
        [$paymentCaptureSecurityToken, $paymentNotifySecurityToken] = $this->getCurrentCaptureAndNotifyPaymentSecurityTokens($payment);

        $this->checkIfAllDataToSendToNexiAreOk($paymentCaptureSecurityToken, $paymentNotifySecurityToken, $payment);
    }

    /**
     * @Given /^I have cancelled (?:|my )Nexi payment$/
     * @When /^I cancel (?:|my )Nexi payment$/
     * @When /^I cancel the payment on Nexi$/
     *
     * @throws JsonException
     */
    public function iCancelMyNexiPayment(): void
    {
        $payment = $this->getCurrentPayment();
        [$paymentCaptureSecurityToken] = $this->getCurrentCaptureAndNotifyPaymentSecurityTokens($payment);

        $cancelResponsePayload = [
            Api::RESULT_FIELD => Result::OUTCOME_ANNULLO,
            'alias' => PaymentContext::NEXI_ALIAS,
            'importo' => (string) $payment->getAmount(),
            'divisa' => Currency::EURO_CURRENCY_CODE,
            'codTrans' => $this->getPaymentCode($payment),
        ];

        // Simulate coming back from Nexi after completed checkout
        $this->session->getDriver()->visit($paymentCaptureSecurityToken->getTargetUrl() . '?' . http_build_query($cancelResponsePayload));
    }

    /**
     * @When /^I try to pay again with Nexi$/
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
        // Do nothing
    }

    /**
     * @Then I should be on the waiting payment processing page
     */
    public function iShouldBeOnTheWaitingPaymentProcessingPage(): void
    {
        $payment = $this->getCurrentPayment();
        $this->paymentProcessPage->verify([
            'tokenValue' => $payment->getOrder()?->getTokenValue(),
        ]);
    }

    /**
     * @Then /^I should be redirected to the thank you page$/
     */
    public function iShouldBeRedirectedToTheThankYouPage(): void
    {
        $this->paymentProcessPage->waitForRedirect();
        Assert::true($this->thankYouPage->hasThankYouMessage());
    }

    /**
     * @Then /^I should be redirected to the order page$/
     */
    public function iShouldBeRedirectedToTheOrderPage(): void
    {
        $this->paymentProcessPage->waitForRedirect();
        $orders = $this->orderRepository->findAll();
        $order = reset($orders);
        Assert::isInstanceOf($order, OrderInterface::class);
        Assert::true($this->orderShowPage->isOpen(['tokenValue' => $order->getTokenValue()]));
    }

    /**
     * @Then I should be notified that my payment is failed
     */
    public function iShouldBeNotifiedThatMyPaymentHasBeenCancelled(): void
    {
        $this->assertNotification('Payment has failed.');
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
            'The data to send to Nexi are not valid! Expected an alias equal to %2$s. Got: %s',
        );
        Assert::eq(
            $this->payumCaptureDoPage->getAmount(),
            $payment->getAmount(),
            'The data to send to Nexi are not valid! Expected an amount equal to %2$s. Got: %s',
        );
        Assert::eq(
            $this->payumCaptureDoPage->getCurrency(),
            Currency::EURO_CURRENCY_CODE,
            'The data to send to Nexi are not valid! Expected a currency equal to %2$s. Got: %s',
        );
        Assert::eq(
            $this->payumCaptureDoPage->getPaymentCode(),
            $this->getPaymentCode($payment),
            'The data to send to Nexi are not valid! Expected a payment code equal to %2$s. Got: %s',
        );
        Assert::eq(
            $this->payumCaptureDoPage->getSuccessUrl(),
            $this->getCaptureUrl($captureToken),
            'The data to send to Nexi are not valid! Expected a success url equal to %2$s. Got: %s',
        );
        Assert::eq(
            $this->payumCaptureDoPage->getBackUrl(),
            $this->getCaptureUrl($captureToken),
            'The data to send to Nexi are not valid! Expected a back url equal to %2$s. Got: %s',
        );
        Assert::eq(
            $this->payumCaptureDoPage->getPostUrl(),
            $this->getNotifyUrl($notifyToken),
            'The data to send to Nexi are not valid! Expected a post url equal to %2$s. Got: %s',
        );
        Assert::eq(
            $this->payumCaptureDoPage->getEmail(),
            $payment->getOrder()?->getCustomer()?->getEmail(),
            'The data to send to Nexi are not valid! Expected an email equal to %2$s. Got: %s',
        );
        Assert::eq(
            $this->payumCaptureDoPage->getLanguageId(),
            'ENG',
            'The data to send to Nexi are not valid! Expected a language id equal to %2$s. Got: %s',
        );
        Assert::eq(
            $this->payumCaptureDoPage->getDescription(),
            '#' . $payment->getOrder()?->getNumber(),
            'The data to send to Nexi are not valid! Expected a description equal to %2$s. Got: %s',
        );
        Assert::eq(
            $this->payumCaptureDoPage->getMac(),
            $this->getMac($payment),
            'The data to send to Nexi are not valid! Expected a mac equal to %2$s. Got: %s',
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
        return $payment->getOrder()?->getNumber() . '-' . $payment->getId();
    }

    private function getNotifyUrl(PaymentSecurityTokenInterface $token): string
    {
        return $this->urlGenerator->generate(
            'payum_notify_do',
            ['payum_token' => $token->getHash()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
    }

    private function getCaptureUrl(PaymentSecurityTokenInterface $token): string
    {
        return $this->urlGenerator->generate(
            'payum_capture_do',
            ['payum_token' => $token->getHash()],
            UrlGeneratorInterface::ABSOLUTE_URL,
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
            'importo' => (string) $payment->getAmount(),
            'divisa' => Currency::EURO_CURRENCY_CODE,
            'codTrans' => $this->getPaymentCode($payment),
            'mac' => $this->getResponseMac($payment, Result::OUTCOME_OK, $date, $time, 'OKAY'),
            'data' => $date,
            'orario' => $time,
            'codAut' => 'OKAY',
        ];
    }

    /**
     * @param PaymentSecurityTokenInterface[] $paymentSecurityTokens
     */
    private function extractCaptureSecurityToken(array $paymentSecurityTokens): PaymentSecurityTokenInterface
    {
        $paymentCaptureSecurityTokens = array_filter($paymentSecurityTokens, static function (PaymentSecurityTokenInterface $token): bool {
            return str_contains($token->getTargetUrl(), 'payment/capture');
        });
        Assert::count($paymentCaptureSecurityTokens, 1, sprintf('Expected 1 payment capture security token, got %s.', count($paymentCaptureSecurityTokens)));

        return array_pop($paymentCaptureSecurityTokens);
    }

    /**
     * @param PaymentSecurityTokenInterface[] $paymentSecurityTokens
     */
    private function extractNotifySecurityToken(array $paymentSecurityTokens): PaymentSecurityTokenInterface
    {
        $paymentNotifySecurityTokens = array_filter($paymentSecurityTokens, static function (PaymentSecurityTokenInterface $token): bool {
            return str_contains($token->getTargetUrl(), 'payment/notify');
        });
        Assert::count($paymentNotifySecurityTokens, 1, sprintf('Expected 1 payment notify security token, got %s.', count($paymentNotifySecurityTokens)));

        return array_pop($paymentNotifySecurityTokens);
    }

    private function assertNotification(string $expectedNotification): void
    {
        $notifications = $this->orderDetails->getNotifications();
        $hasNotifications = '';

        foreach ($notifications as $notification) {
            $hasNotifications .= $notification;
            if ($notification === $expectedNotification) {
                return;
            }
        }

        throw new \RuntimeException(sprintf('There is no notification with "%s". Got "%s"', $expectedNotification, $hasNotifications));
    }
}
