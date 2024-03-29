<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PortfolioController extends AbstractController
{
    #[Route('', name: 'portfolio', defaults: ['_menu' => 'portfolio'])]
    public function index(): Response
    {
        return $this->render('portfolio/index.html.twig');
    }
}
