<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PortfolioController extends AbstractController
{
    /**
     * @Route("", name="portfolio", defaults={"_menu"="portfolio"})
     */
    public function index()
    {
        return $this->render('portfolio/index.html.twig');
    }
}
