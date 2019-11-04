<?php

namespace App\Model;

class Talk
{
    public $title;
    public $description;
    public $duration;
    public $date;
    public $lastModified;
    public $content;

    public function __construct(
        string $title,
        string $description,
        int $duration,
        \DateTime $date,
        string $slides = null,
        string $video = null
    ) {
        $this->title = $title;
        $this->description = $description;
        $this->duration = $duration;
        $this->date = $date;
        $this->slides = $slides;
        $this->video = $video;
    }
}
