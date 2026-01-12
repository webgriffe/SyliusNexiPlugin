<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class NexiPaymentMethodUnique extends Constraint
{
    public string $message = 'webgriffe_sylius_nexi.payment_method.unique';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
