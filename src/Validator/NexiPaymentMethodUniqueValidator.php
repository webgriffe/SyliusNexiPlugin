<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Validator;

use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\PaymentMethodRepositoryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Api;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class NexiPaymentMethodUniqueValidator extends ConstraintValidator
{
    /**
     * @param PaymentMethodRepositoryInterface<PaymentMethodInterface> $paymentMethodRepository
     */
    public function __construct(
        private readonly PaymentMethodRepositoryInterface $paymentMethodRepository,
    ) {
    }

    /**
     * @param mixed|PaymentMethodInterface $value
     */
    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$value instanceof PaymentMethodInterface) {
            throw new UnexpectedValueException($value, PaymentMethodInterface::class);
        }

        if (!$constraint instanceof NexiPaymentMethodUnique) {
            throw new UnexpectedValueException($constraint, NexiPaymentMethodUnique::class);
        }

        $gatewayConfig = $value->getGatewayConfig();
        /** @psalm-suppress DeprecatedMethod */
        if ($gatewayConfig === null || $gatewayConfig->getFactoryName() !== Api::CODE) {
            return;
        }

        /** @var PaymentMethodInterface[] $paymentMethods */
        $paymentMethods = $this->paymentMethodRepository->findAll();
        /** @psalm-suppress DeprecatedMethod */
        $paymentMethodsWithSameGatewayConfig = array_filter(
            $paymentMethods,
            static fn (PaymentMethodInterface $paymentMethod) => $paymentMethod->getGatewayConfig()?->getFactoryName() === $gatewayConfig->getFactoryName(),
        );
        if (count($paymentMethodsWithSameGatewayConfig) > 1 ||
            (count($paymentMethodsWithSameGatewayConfig) === 1 && reset($paymentMethodsWithSameGatewayConfig) !== $value)
        ) {
            $this->context
                ->buildViolation($constraint->message)
                ->atPath('gatewayConfig')
                ->addViolation()
            ;
        }
    }
}
