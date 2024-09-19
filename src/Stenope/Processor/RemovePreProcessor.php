<?php

declare(strict_types=1);

namespace App\Stenope\Processor;

use Stenope\Bundle\Behaviour\HtmlCrawlerManagerInterface;
use Stenope\Bundle\Behaviour\ProcessorInterface;
use Stenope\Bundle\Content;

/**
 * Apply syntax coloration to code blocs
 */
class RemovePreProcessor implements ProcessorInterface
{
    public function __construct(
        private HtmlCrawlerManagerInterface $crawlers,
    ) {
    }

    public function __invoke(array &$data, Content $content): void
    {
        if (!isset($data['content'])) {
            return;
        }

        $crawler = $this->crawlers->get($content, $data, 'content');

        if ($crawler === null) {
            throw new \Exception('Could not instanciate crawler');
        }

        /** @var \DOMElement $element */
        foreach ($crawler->filter('pre') as $element) {
            $this->simplify($element);
        }

        $this->crawlers->save($content, $data, 'content');
    }

    private function simplify(\DOMElement $element): void
    {
        /** @var \DOMElement */
        $child = $element->firstChild;

        $classes = array_map('trim', array_unique(array_filter(explode(' ', $element->getAttribute('class') . ' ' . $child->getAttribute('class')))));
        $child->setAttribute('class', implode(' ', $classes));

        if ($element->parentNode === null) {
            throw new \Exception('Element has no parent.');
        }

        $element->parentNode->replaceChild($child, $element);
    }
}
