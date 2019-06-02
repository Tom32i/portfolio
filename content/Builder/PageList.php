<?php

namespace Content\Builder;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * Page List
 */
class PageList
{
    private $urls = [];

    public function add(string $url)
    {
        if (!isset($this->urls[$url])) {
            $this->urls[$url] = true;
        }
    }

    public function markAsDone(string $url)
    {
        if (isset($this->urls[$url])) {
            $this->urls[$url] = false;
        }
    }

    public function getNext(): string
    {
        return current($this->getQueue());
    }

    private function getQueue(): array
    {
        return array_keys(array_filter($this->urls));
    }
}

