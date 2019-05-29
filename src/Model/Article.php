<?php

namespace App\Model;

class Article
{
    public $title;
    public $slug;
    public $language;
    public $date;
    public $lastModified;
    public $content;

    public function __construct(
        string $title,
        string $slug,
        \DateTime $date,
        \DateTime $lastModified,
        string $language,
        string $content
    ) {
        $this->title = $title;
        $this->slug = $slug;
        $this->language = $language;
        $this->date = $date;
        $this->lastModified = $lastModified;
        $this->content = $content;
    }
}
