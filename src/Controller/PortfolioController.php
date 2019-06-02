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
        ini_set('memory_limit', '2048M');

        return $this->render('portfolio/index.html.twig');
    }

    /**
     * @Route("test.json", name="test_json", defaults={"_format": "json"}, options={"mapped": false})
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
