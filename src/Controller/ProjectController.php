<?php

namespace App\Controller;

use App\Model\Project;
use Stenope\Bundle\ContentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ProjectController extends AbstractController
{
    private ContentManager $manager;

    public function __construct(ContentManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @Route("/creations", name="project", defaults={"_menu"="project"})
     */
    public function list()
    {
        return $this->render('project/index.html.twig', [
            'projects' => $this->manager->getContents(Project::class, ['priority' => false]),
        ]);
    }
}
