<?php

namespace App\Model;

class Article
{
    public string $title;
    public string $slug;
    public string $description;
    public \DateTimeImmutable $date;
    public \DateTimeImmutable $lastModified;
    public string $content;
    public string $language = 'fr';
    public ?string $cover = null;

    /*public function __construct(
        string $title,
        string $slug,
        string $description,
        \DateTimeImmutable $date,
        \DateTimeImmutable $lastModified,
        string $content,
        string $language = 'fr',
        ?string $cover = null
    ) {
        $this->title = $title;
        $this->slug = $slug;
        $this->description = $description;
        $this->date = $date;
        $this->lastModified = $lastModified;
        $this->content = $content;
        $this->language = $language;
        $this->cover = $cover;
    }*/
}
