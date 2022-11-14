<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusNexiPlugin\Behat\Context\Ui;

use Behat\Behat\Context\Context;
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
use Webgriffe\LibQuiPago\Signature\DefaultSignatureHashingManager;
use Webmozart\Assert\Assert;

final class NexiContext implements Context
{
    public function __construct(
        private PayumCaptureDoPageInterface $payumCaptureDoPage,
        private RepositoryInterface $paymentTokenRepository,
        private PaymentRepositoryInterface $paymentRepository,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * @When I complete the payment on Nexi
     */
    public function iCompleteThePaymentOnNexi(): void
    {
        $paymentSecurityToken = $this->getCurrentCapturePaymentSecurityToken();
        $payment = $this->getCurrentPayment();
        Assert::true($this->payumCaptureDoPage->isOpen(['payum_token' => $paymentSecurityToken->getHash()]), 'The current page is not the capture page.');
        $this->assertPageHasValidPaymentDetails($payment, $paymentSecurityToken->getHash());
    }

    private function getCurrentCapturePaymentSecurityToken(): PaymentSecurityTokenInterface
    {
        /** @var PaymentSecurityTokenInterface[] $paymentSecurityTokens */
        $paymentSecurityTokens = $this->paymentTokenRepository->findAll();
        Assert::count($paymentSecurityTokens, 2);
        /** @var PaymentSecurityTokenInterface $paymentSecurityToken */
        $paymentSecurityTokens = array_filter($paymentSecurityTokens, static function (PaymentSecurityTokenInterface $token): bool {
            return $token->getAfterUrl() !== null;
        });
        Assert::count($paymentSecurityTokens, 1);
        $paymentSecurityToken = reset($paymentSecurityTokens);
        Assert::isInstanceOf($paymentSecurityToken, PaymentSecurityTokenInterface::class);

        return $paymentSecurityToken;
    }

    private function getCurrentPayment(): PaymentInterface
    {
        /** @var PaymentInterface[] $payments */
        $payments = $this->paymentRepository->findAll();
        Assert::count($payments, 1);
        $payment = reset($payments);
        Assert::isInstanceOf($payment, PaymentInterface::class);

        return $payment;
    }

    public function assertPageHasValidPaymentDetails(PaymentInterface $payment, string $hash): void
    {
        // TODO: Why config parameters are not loaded?
        $this->urlGenerator->setContext(new RequestContext('', 'GET', '127.0.0.1:8080', 'https'));
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
            $this->urlGenerator->generate(
                'payum_capture_do',
                ['payum_token' => $hash],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'The data to send to Nexi are not valid! Expected a success url equal to %2$s. Got: %s'
        );
        Assert::eq(
            $this->payumCaptureDoPage->getBackUrl(),
            $this->urlGenerator->generate(
                'payum_capture_do',
                ['payum_token' => $hash],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'The data to send to Nexi are not valid! Expected a back url equal to %2$s. Got: %s'
        );
        Assert::eq(
            $this->payumCaptureDoPage->getPostUrl(),
            $this->urlGenerator->generate(
                'payum_notify_do_unsafe',
                ['gateway' => 'nexi', 'notify_token' => $hash],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
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

    private function getPaymentCode(PaymentInterface $payment): string
    {
        return (string)$payment->getOrder()->getNumber() . '-' . (string)$payment->getId();
    }
}
