<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusNexiPlugin\Application\src\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class NexiGatewayController extends AbstractController
{
    public function payAction(Request $request): Response
    {
        return new Response();
    }
}
