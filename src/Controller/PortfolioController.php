<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Event;
use App\Model\Game;
use App\Model\Talk;
use App\Model\Tool;
use Stenope\Bundle\ContentManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PortfolioController extends AbstractController
{
    public function __construct(
        private ContentManagerInterface $manager,
    ) {
    }

    #[Route('/', name: 'portfolio', defaults: ['_locale' => 'fr', '_menu' => 'portfolio'])]
    #[Route('/en/', name: 'portfolio_en', defaults: ['_locale' => 'en', '_menu' => 'portfolio'])]
    public function index(): Response
    {
        $games = $this->manager->getContents(Game::class, ['priority' => false]);
        $featured = array_find($games, static fn (Game $game): bool => $game->featured);

        return $this->render('portfolio/index.html.twig', [
            'featured' => $featured,
            'games' => array_filter($games, static fn (Game $game): bool => $game !== $featured),
            'talks' => $this->manager->getContents(Talk::class, ['date' => false]),
            'events' => $this->manager->getContents(Event::class, ['priority' => false]),
            'tools' => $this->manager->getContents(Tool::class, ['priority' => false]),
        ]);
    }

    /**
     * The former "Créations" page now lives in the one-pager sections.
     */
    #[Route('/creations', name: 'work', options: ['stenope' => ['sitemap' => false]])]
    public function work(): Response
    {
        return $this->render('portfolio/redirect.html.twig', [
            'target' => $this->generateUrl('portfolio'),
        ]);
    }
}
