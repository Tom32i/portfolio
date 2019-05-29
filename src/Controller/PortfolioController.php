<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PortfolioController extends AbstractController
{
    /**
     * @Route("", name="home")
     */
    public function index()
    {
        return $this->render('portfolio/index.html.twig');
    }

    /**
     * @Route("test.json", name="test_json")
     */
    public function testJson()
    {
        return $this->json([
            'foo' => true,
            'bar' => 45,
            'baz' => 'baz'
        ]);
    }
}
