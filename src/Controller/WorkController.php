<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Game;
use App\Model\Talk;
use App\Model\Tool;
use Stenope\Bundle\ContentManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WorkController extends AbstractController
{
    public function __construct(
        private ContentManagerInterface $manager,
    ) {
    }

    #[Route('/creations', name: 'work', defaults: ['_menu' => 'work'])]
    public function list(): Response
    {
        return $this->render('work/index.html.twig', [
            'games' => $this->manager->getContents(Game::class, ['priority' => false]),
            'talks' => $this->manager->getContents(Talk::class, ['date' => false]),
            'tools' => $this->manager->getContents(Tool::class, ['priority' => false]),
        ]);
    }
}
