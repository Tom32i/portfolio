<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Project;
use Stenope\Bundle\ContentManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ProjectController extends AbstractController
{
    private ContentManagerInterface $manager;

    public function __construct(ContentManagerInterface $manager)
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
