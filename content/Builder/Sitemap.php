<?php

namespace Content\Builder;

/**
 * Sitemap
 */
class Sitemap implements \Iterator, \Countable
{
    /**
     * Mapped URLs
     *
     * @var array
     */
    private $urls = [];

    /**
     * Position
     *
     * @var integer
     */
    private $position = 0;

    /**
     * Add location
     *
     * @param string $location The URL
     * @param DateTime $lastModified Date of last modification
     * @param integer $priority Location priority
     * @param string $frequency
     */
    public function add(string $location, \DateTime $lastModified = null, int $priority = null, string $frequency = null)
    {
        $url = ['location' => $location];

        if ($priority === null && empty($this->urls)) {
            $priority = 0;
        }

        if ($lastModified) {
            $url['lastModified'] = $lastModified;
        }

        if ($priority !== null) {
            $url['priority'] = $priority;
        }

        if ($frequency) {
            $url['frequency'] = $frequency;
        }

        $this->urls[$location] = $url;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->urls[array_keys($this->urls)[$this->position]];
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return array_keys($this->urls)[$this->position];
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return isset(array_keys($this->urls)[$this->position]);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->urls);
    }
}
