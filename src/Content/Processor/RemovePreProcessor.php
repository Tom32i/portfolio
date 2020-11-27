<?php

namespace App\Content\Processor;

use Stenope\Bundle\Behaviour\ProcessorInterface;
use Stenope\Bundle\Content;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Apply syntax coloration to code blocs
 */
class RemovePreProcessor implements ProcessorInterface
{
    public function __invoke(array &$data, string $type, Content $content): void
    {
        if (!isset($data['content'])) {
            return;
        }

        $crawler = new Crawler($data['content']);

        try {
            $crawler->html();
        } catch (\Exception $e) {
            // Content is not valid HTML.
            return;
        }

        $crawler = new Crawler($data['content']);

        foreach ($crawler->filter('pre') as $element) {
            $this->simplify($element);
        }

        $data['content'] = $crawler->html();
    }

    private function simplify(\DOMElement $element): void
    {
        $classes = array_map('trim', array_unique(array_filter(explode(' ', $element->getAttribute('class') . ' ' . $element->firstChild->getAttribute('class')))));
        $element->firstChild->setAttribute('class', implode(' ', $classes));
        $element->parentNode->replaceChild($element->firstChild, $element);
    }
}
