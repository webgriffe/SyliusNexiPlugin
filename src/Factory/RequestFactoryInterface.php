<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Factory;

use Payum\Core\Security\TokenInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Webgriffe\LibQuiPago\PaymentInit\Request;

interface RequestFactoryInterface
{
    public function create(
        string $merchantAlias,
        PaymentInterface $payment,
        TokenInterface $token,
        TokenInterface $notifyToken
    ): Request;
}
