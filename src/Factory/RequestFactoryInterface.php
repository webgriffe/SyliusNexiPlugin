<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Factory;

use Payum\Core\Security\TokenInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Webgriffe\LibQuiPago\PaymentInit\Request;
use Webgriffe\SyliusNexiPlugin\Payum\Nexi\Api;

interface RequestFactoryInterface
{
    public function create(Api $api, OrderInterface $order, PaymentInterface $payment, TokenInterface $token): Request;
}
