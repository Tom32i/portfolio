<?php

namespace App\Controller;

use App\Model\Article;
use Content\ContentManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/blog", name="blog")
 */
class BlogController extends AbstractController
{
    private $manager;

    public function __construct(ContentManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @Route("", name="")
     */
    public function list()
    {
        $articles = $this->manager->getContents(Article::class, 'date');

        return $this->render('blog/index.html.twig', [
            'articles' => $articles,
        ]);
    }

    /**
     * @Route("/{slug}", name="_article")
     */
    public function article(string $slug)
    {
        return $this->render('blog/article.html.twig', [
            'article' => $this->manager->getContent(Article::class, $slug)
        ]);
    }
}
