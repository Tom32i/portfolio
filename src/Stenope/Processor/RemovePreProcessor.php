<?php

namespace App\Stenope\Processor;

use Stenope\Bundle\Behaviour\HtmlCrawlerManagerInterface;
use Stenope\Bundle\Behaviour\ProcessorInterface;
use Stenope\Bundle\Content;

/**
 * Apply syntax coloration to code blocs
 */
class RemovePreProcessor implements ProcessorInterface
{
    private HtmlCrawlerManagerInterface $crawlers;

    public function __construct(HtmlCrawlerManagerInterface $crawlers)
    {
        $this->crawlers = $crawlers;
    }

    public function __invoke(array &$data, Content $content): void
    {
        if (!isset($data['content'])) {
            return;
        }

        $crawler = $this->crawlers->get($content, $data, 'content');

        /** @var \DOMElement $element */
        foreach ($crawler->filter('pre') as $element) {
            $this->simplify($element);
        }

        $this->crawlers->save($content, $data, 'content');
    }

    private function simplify(\DOMElement $element): void
    {
        $classes = array_map('trim', array_unique(array_filter(explode(' ', $element->getAttribute('class') . ' ' . $element->firstChild->getAttribute('class')))));
        $element->firstChild->setAttribute('class', implode(' ', $classes));
        $element->parentNode->replaceChild($element->firstChild, $element);
    }
}
