<?php

namespace App\Controller;

use App\Model\Talk;
use Content\ContentManager;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PortfolioController extends AbstractController
{
    public function __construct(ContentManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @Route("", name="home")
     */
    public function index()
    {
        //ini_set('memory_limit', '2048M');

        return $this->render('portfolio/index.html.twig', [
            'talks' => $this->manager->getContents(Talk::class, ['date' => false]),
        ]);
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
