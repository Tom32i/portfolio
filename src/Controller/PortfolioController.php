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
     * @Route("", name="home")
     */
    public function index()
    {
        //ini_set('memory_limit', '2048M');

        return $this->render('portfolio/index.html.twig', [
            'talks' => $this->manager->getContents(Talk::class, ['date' => false]),
            'projects' => $this->manager->getContents(Project::class, ['date' => false]),
        ]);
    }

    /**
     * @Route("/short", name="short")
     */
    public function short()
    {
        return $this->render('portfolio/short.html.twig', [
            'talks' => $this->manager->getContents(Talk::class, ['date' => false]),
            'projects' => $this->manager->getContents(Project::class, ['date' => false]),
        ]);
    }
}
