<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Talk;
use Stenope\Bundle\ContentManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class TalkController extends AbstractController
{
    private ContentManagerInterface $manager;

    public function __construct(ContentManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @Route("/talk", name="talk", defaults={"_menu"="talk"})
     */
    public function list()
    {
        return $this->render('talk/index.html.twig', [
            'talks' => $this->manager->getContents(Talk::class, ['date' => false]),
        ]);
    }
}
