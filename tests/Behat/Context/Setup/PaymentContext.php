<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusNexiPlugin\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use Doctrine\Persistence\ObjectManager;
use Sylius\Behat\Service\SharedStorageInterface;
use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Payment\Repository\PaymentMethodRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

final class PaymentContext implements Context
{
    public function __construct(
        private SharedStorageInterface $sharedStorage,
        private PaymentMethodRepositoryInterface $paymentMethodRepository,
        private ExampleFactoryInterface $paymentMethodExampleFactory,
        private FactoryInterface $paymentMethodTranslationFactory,
        private ObjectManager $paymentMethodManager,
        private array $gatewayFactories,
    ) {
    }

    /**
     * @Given the store has (also) a payment method :paymentMethodName with a code :paymentMethodCode and Nexi Simple Payment Checkout gateway
     */
    public function theStoreHasPaymentMethodWithCodeAndPaypalExpressCheckoutGateway(
        $paymentMethodName,
        $paymentMethodCode
    ): void {
        $paymentMethod = $this->createPaymentMethod($paymentMethodName, $paymentMethodCode, 'Nexi Simple Payment Checkout');
        $paymentMethod->getGatewayConfig()->setConfig([
            'sandbox' => false,
            'alias' => 'ALIAS_WEB_111111',
            'mac_key' => '83Y4TDI8W7Y4EWIY48TWT',
        ]);

        $this->paymentMethodManager->flush();
    }

    private function createPaymentMethod(
        string $name,
        string $code,
        string $gatewayFactory = 'Nexi Simple Payment Checkout',
        string $description = '',
        bool $addForCurrentChannel = true,
        ?int $position = null
    ): PaymentMethodInterface {
        $gatewayFactory = array_search($gatewayFactory, $this->gatewayFactories, true);

        /** @var PaymentMethodInterface $paymentMethod */
        $paymentMethod = $this->paymentMethodExampleFactory->create([
            'name' => ucfirst($name),
            'code' => $code,
            'description' => $description,
            'gatewayName' => $gatewayFactory,
            'gatewayFactory' => $gatewayFactory,
            'enabled' => true,
            'channels' => ($addForCurrentChannel && $this->sharedStorage->has('channel')) ? [$this->sharedStorage->get('channel')] : [],
        ]);

        if (null !== $position) {
            $paymentMethod->setPosition((int) $position);
        }

        $this->sharedStorage->set('payment_method', $paymentMethod);
        $this->paymentMethodRepository->add($paymentMethod);

        return $paymentMethod;
    }
}
