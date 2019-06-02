<?php

namespace App\Serializer;

use App\Model\Article;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ArticleRssNormalizer implements NormalizerInterface
{
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function normalize($object, $format = null, array $context = [])
    {
        $url = $this->urlGenerator->generate('blog_article', ['slug' => $object->slug], UrlGeneratorInterface::ABSOLUTE_URL);

        return [
            'title' => $object->title,
            'description' => $object->description ?? '',
            'pubDate' => $object->date,
            'guid' => $url,
            'link' => $url,
        ];
    }

    public function supportsNormalization($data, $format = null)
    {
        return is_a($data, Article::class, true) && $format == 'rss';
    }
}
