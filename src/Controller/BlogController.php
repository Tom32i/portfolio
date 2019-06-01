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
        $lastModified = max(array_map(function ($article) { return $article->lastModified; }, $articles));

        return $this->render('blog/index.html.twig', [
            'articles' => $articles,
        ])->setLastModified($lastModified);
    }

    /**
     * @Route("/{slug}", name="_article")
     */
    public function article(string $slug)
    {
        $article = $this->manager->getContent(Article::class, $slug);

        return $this->render('blog/article.html.twig', [
            'article' => $article,
        ])->setLastModified($article->lastModified);
    }
}
