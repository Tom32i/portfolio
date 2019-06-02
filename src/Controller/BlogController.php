<?php

namespace App\Controller;

use App\Model\Article;
use Content\ContentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/blog", name="blog")
 */
class BlogController extends AbstractController
{
    private $manager;

    public function __construct(ContentManager $manager, SerializerInterface $serializer, Packages $assets)
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
        $articles = $this->manager->getContents(Article::class, 'date');
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
        $articles = $this->manager->getContents(Article::class, 'date');

        return $this->render('@Content/rss.xml.twig', [
            'title' => 'Thomas Jarrand Blog Technique',
            'description' => '',
            'webmaster' => [
                'email' => 'thomas.jarrand@gmail.com',
                'name' => 'Thomas Jarrand',
            ],
            'image' => [
                'url' => $this->assets->getUrl('/img/thomas-jarrand-blog.rss.png'),
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
        ])->setLastModified($article->lastModified);
    }
}
