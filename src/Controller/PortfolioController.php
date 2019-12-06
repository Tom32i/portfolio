<?php

namespace App\Controller;

use App\Model\Talk;
use App\Model\Project;
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
     * @Route("", name="portfolio")
     */
    public function index()
    {
        return $this->render('portfolio/index.html.twig', [
            //'talks' => $this->manager->getContents(Talk::class, ['date' => false]),
            //'projects' => $this->manager->getContents(Project::class, ['date' => false]),
        ]);
    }
}
