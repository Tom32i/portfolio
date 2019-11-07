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

    protected function blockCodeComplete($Block)
    {
        // Drop the <pre>
        $Block['element'] = [
            'name' => 'code',
            'text' => $Block['element']['text']['text'],
        ];

        return $Block;
    }

    protected function blockFencedCodeComplete($Block)
    {
        $language = $this->getLanguage($Block);
        $content = $Block['element']['text']['text'];

        // Drop the <pre> + highlight
        $Block['element'] = [
            'name' => 'code',
            'handler' => 'noescape',
            'text' => $this->getCode($content, $language),
            'attributes' => [
                'class' => $language,
            ],
        ];

        return $Block;
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
    protected function getCode(string $text, string $language): string
    {
        if ($this->highlighter) {
            return $this->highlighter->highlight($text, $language);
        }

        return $this->escape($text);
    }

    /**
     * No espace filter
     */
    protected function noescape(string $text): string
    {
        return $text;
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
