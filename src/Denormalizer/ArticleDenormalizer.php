<?php

namespace App\Denormalizer;

use App\Model\Article;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class ArticleDenormalizer implements DenormalizerInterface
{
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        return new Article(
            $this->get($data, 'title'),
            $this->get($data, 'slug'),
            $this->get($data, 'date'),
            $this->get($data, 'language', 'fr'),
            $this->get($data, 'content')
        );
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return is_a($type, Article::class, true);
    }

    private function get(array $data, string $property, $default = null)
    {
        return isset($data[$property]) ? $data[$property] : $default;
    }
}
