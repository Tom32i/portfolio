<?php

namespace App\Model;

class Project
{
    public $title;
    public $description;
    public $url;
    public $date;
    public $image;

    public function __construct(
        string $title,
        string $description,
        string $url,
        \DateTime $date,
        string $image
    ) {
        $this->title = $title;
        $this->description = $description;
        $this->url = $url;
        $this->date = $date;
        $this->image = $image;
    }

    public function getSiteName(): string
    {
        return trim(preg_replace('/^https?:\/\/(.+)$/', '$1', $this->url), '/');
    }
}
