<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Model\Article;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ArticleRssNormalizer implements NormalizerInterface
{
    public function __construct(
        private UrlGeneratorInterface $router,
    ) {
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        if (!(\is_object($object) && is_a($object, Article::class))) {
            throw new \Exception('Must be an Article');
        }

        $url = $this->router->generate('blog_article', ['article' => $object->slug], UrlGeneratorInterface::ABSOLUTE_URL);

        return [
            'title' => $object->title,
            'description' => $object->description ?? '',
            'pubDate' => $object->date,
            // Articles moved to trailing-slash URLs: keep the former URL as guid, so feed readers
            // don't see every past article as new (it still redirects to the article).
            'guid' => rtrim($url, '/'),
            'link' => $url,
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return \is_object($data) && is_a($data, Article::class, true) && $format === 'rss';
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Article::class => true,
        ];
    }
}
