<?php

namespace App\Model;

class Project
{
    public string $title;
    public string $description;
    public string $url;
    public \DateTimeImmutable $date;
    public \DateTimeImmutable $lastModified;
    public string $image;

    /*public function __construct(
        string $title,
        string $description,
        string $url,
        \DateTimeImmutable $date,
        \DateTimeImmutable $lastModified,
        string $image
    ) {
        $this->title = $title;
        $this->description = $description;
        $this->url = $url;
        $this->date = $date;
        $this->lastModified = $lastModified;
        $this->image = $image;
    }*/

    public function getSiteName(): string
    {
        return trim(preg_replace('/^https?:\/\/(.+)$/', '$1', $this->url), '/');
    }
}
