<?php

namespace Content\Behaviour;

interface HighlighterInterface
{
    /**
     * Highlight the given code
     *
     * @param string $value
     * @param string $language
     *
     * @return string
     */
    public function highlight(string $value,  string $language): string;
}
