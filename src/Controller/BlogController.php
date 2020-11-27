<?php

namespace App\Controller;

use App\Model\Article;
use Stenope\Bundle\ContentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @Route("/blog", name="blog", defaults={"_menu"="blog"})
 */
class BlogController extends AbstractController
{
    private ContentManager $manager;
    private NormalizerInterface $serializer;
    private Packages $assets;

    public function __construct(ContentManager $manager, NormalizerInterface $serializer, Packages $assets)
    {
        $this->manager = $manager;
        $this->serializer = $serializer;
        $this->assets = $assets;
    }

    /**
     * @Route("", name="")
     */
    public function list()
    {
        $articles = $this->manager->getContents(Article::class, ['date' => false]);
        $lastModified = max(array_map(function ($article) { return $article->lastModified; }, $articles));

        return $this->render('blog/index.html.twig', [
            'articles' => $articles,
        ])->setLastModified($lastModified);
    }

    /**
     * @Route("/feed.rss", name="_feed", defaults={"_format": "atom"}, options={"mapped": false})
     */
    public function feed()
    {
        $articles = $this->manager->getContents(Article::class, ['date' => true]);

        return $this->render('@Stenope/rss.xml.twig', [
            'title' => 'Thomas Jarrand Blog Technique',
            'description' => '',
            'webmaster' => [
                'email' => 'thomas.jarrand@gmail.com',
                'name' => 'Thomas Jarrand',
            ],
            'image' => [
                'url' => $this->assets->getUrl('/ms-icon-144x144.png'),
                'width' => 144,
                'height' => 144,
            ],
            'items' => $this->serializer->normalize($articles, 'rss'),
        ]);
    }

    /**
     * @Route("/{slug}", name="_article")
     */
    public function article(string $slug)
    {
        $article = $this->manager->getContent(Article::class, $slug);

        return $this->render('blog/article.html.twig', [
            'article' => $article,
            'lastestArticles' => \array_slice($this->manager->getContents(Article::class, ['date' => false]), 0, 3),
        ])->setLastModified($article->lastModified);
    }

    public function latest(int $max = 3)
    {
        $articles = $this->manager->getContents(Article::class, ['date' => false]);

        return $this->render('blog/latest.html.twig', [
            'articles' => \array_slice($articles, 0, $max),
        ]);
    }
}
