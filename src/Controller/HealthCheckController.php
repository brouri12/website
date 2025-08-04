<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HealthCheckController
{
    #[Route('/health', name: 'health_check')]
    public function check(): Response
    {
        return new Response('OK', Response::HTTP_OK);
    }
}
