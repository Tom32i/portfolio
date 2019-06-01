<?php

namespace Content\Decoder;

use Content\Behaviour\ContentDecoderInterface;
use Content\Behaviour\HighlighterInterface;
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
     * Code highlighter
     *
     * @var HighlighterInterface
     */
    protected $highlighter;

    /**
     * Constructor
     *
     * @param HighlighterInterface $highlighter
     */
    public function __construct(HighlighterInterface $highlighter = null)
    {
        $this->highlighter = $highlighter;
    }

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

        $crawler->filter('code')->each(function (Crawler $node) {
            if ($language = $node->attr('highlight')) {
                $this->setContent($node, $this->highlighter->highlight(trim($node->html()), $language));
            };
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

    public function setContent(Crawler $node, string $content): void
    {
        $element = $node->getNode(0);

        $element->nodeValue = '';

        $child = $element->ownerDocument->createDocumentFragment();

        $child->appendXML($content);

        $element->appendChild($child);
    }
}
