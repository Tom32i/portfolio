<?php

namespace Content\Service;

use Content\Behaviour\HighlighterInterface;
use Parsedown as BaseParsedown;

/**
 * Parsedown
 */
class Parsedown extends BaseParsedown
{
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

    protected function blockCode($Line, $Block = null)
    {
        if (isset($Block) and ! isset($Block['type']) and ! isset($Block['interrupted']))
        {
            return;
        }

        if ($Line['indent'] >= 4)
        {
            $Block['element']['text']['text'] = substr($Line['body'], 4);

            return $Block;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function blockCodeComplete($Block)
    {
        $Block['element']['text']['text'] = $this->getCode($Block);
        $Block['element'] = $Block['element']['text'];
        $Block['element']['handler'] = 'noescape';

        return $Block;
    }

    /**
     * {@inheritdoc}
     */
    protected function blockFencedCodeComplete($Block)
    {
        $Block['element']['text']['text'] = $this->getCode($Block);
        $Block['element'] = $Block['element']['text'];
        $Block['element']['handler'] = 'noescape';

        return $Block;
    }

    protected function noescape($text)
    {
        return $text;
    }

    /**
     * {@inheritdoc}
     */
    protected function inlineLink($Excerpt)
    {
        $data = parent::inlineLink($Excerpt);

        if (preg_match('#(https?:)?//#i', $data['element']['attributes']['href'])) {
            $data['element']['attributes']['target'] = '_blank';
        }

        return $data;
    }

    protected function inlineCode($Excerpt)
    {
        $data = parent::inlineCode($Excerpt);

        $data['element']['name'] = 'span';
        $data['element']['attributes']['class'] = 'inline-code';

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function blockHeader($Line)
    {
        $Block = parent::blockHeader($Line);

        $Block['element']['attributes']['id'] = $this->getId($Block);

        return $Block;
    }

    /**
     * Process code content
     *
     * @param string $text
     *
     * @return string
     */
    protected function getCode($Block)
    {
        if (!isset($Block['element']['text']['text'])) {
            return null;
        }

        $text = $Block['element']['text']['text'];

        if ($this->highlighter && $language = $this->getLanguage($Block)) {
            return $this->highlighter->highlight($text, $language);
        }

        return $this->escape($text);
    }

    /**
     * Get language of the given block
     *
     * @param array $Block
     *
     * @return string
     */
    protected function getLanguage($Block)
    {
        if (!isset($Block['element']['text']['attributes'])) {
            return null;
        }

        return substr($Block['element']['text']['attributes']['class'], strlen('language-'));
    }

    /**
     * Get ID for the given block
     *
     * @param array $Block
     *
     * @return string
     */
    protected function getId($Block)
    {
        return preg_replace('#[^a-z]+#i', '-', strtolower($Block['element']['text']));
    }
}
