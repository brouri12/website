<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontWebController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->render('frontweb/home.html.twig', [
            'popularProducts' => [], // Add your products data here
            'promotedProducts' => [], // Add your promoted products data here
            'categories' => [], // Add your categories data here
        ]);
    }
}
