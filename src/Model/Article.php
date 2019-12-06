<?php

namespace App\Model;

class Article
{
    public $title;
    public $slug;
    public $description;
    public $language;
    public $date;
    public $lastModified;
    public $content;

    public function __construct(
        string $title,
        string $slug,
        string $description,
        \DateTime $date,
        \DateTime $lastModified,
        string $language,
        string $content
    ) {
        $this->title = $title;
        $this->slug = $slug;
        $this->description = $description;
        $this->language = $language;
        $this->date = $date;
        $this->lastModified = $lastModified;
        $this->content = $content;
    }
}
