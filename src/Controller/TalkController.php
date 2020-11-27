<?php

namespace App\Controller;

use App\Model\Talk;
use Stenope\Bundle\ContentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

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
