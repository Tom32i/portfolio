<?php

namespace App\Controller;

use App\Model\Talk;
use Stenope\Bundle\ContentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class TalkController extends AbstractController
{
    private ContentManager $manager;

    public function __construct(ContentManager $manager)
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
