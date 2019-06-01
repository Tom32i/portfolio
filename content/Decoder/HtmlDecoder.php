<?php

namespace Content\Decoder;

use Content\Behaviour\ContentDecoderInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Parse Html data
 */
class HtmlDecoder implements ContentDecoderInterface
{
    /**
     * Supported format
     */
    const FORMAT = 'html';

    /**
     * {@inheritdoc}
     */
    public function decode($data, $format, array $context = [])
    {
        $crawler = new Crawler($data);

        $attributes = [];

        $crawler->filterXPath('//head/meta')->each(function ($node) use (&$attributes) {
            $attributes[$node->attr('name')] = $node->attr('content');
        });

        return array_merge(
            $attributes,
            [
                'title' => $crawler->filterXPath('//head/title')->text(),
                'content' => $crawler->filterXPath('//body')->html(),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDecoding($format)
    {
        return self::FORMAT === $format;
    }
}
