<?php

namespace App\Model;

class Article
{
    public string $title;
    public string $slug;
    public string $description;
    public string $language;
    public \DateTime $date;
    public \DateTime $lastModified;
    public string $content;
    public ?string $cover;

    public function __construct(
        string $title,
        string $slug,
        string $description,
        \DateTime $date,
        \DateTime $lastModified,
        string $language,
        string $content,
        ?string $cover = null
    ) {
        $this->title = $title;
        $this->slug = $slug;
        $this->description = $description;
        $this->language = $language;
        $this->date = $date;
        $this->lastModified = $lastModified;
        $this->content = $content;
        $this->cover = $cover;
    }
}
