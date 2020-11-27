<?php

namespace App\Model;

class Talk
{
    public string $title;
    public string $description;
    public int $duration;
    public \DateTimeImmutable $date;
    public \DateTimeImmutable $lastModified;
    public ?string $slides = null;
    public ?string $video = null;

    /*public function __construct(
        string $title,
        string $description,
        int $duration,
        \DateTime $date,
        \DateTime $lastModified,
        string $slides = null,
        string $video = null
    ) {
        $this->title = $title;
        $this->description = $description;
        $this->duration = $duration;
        $this->date = $date;
        $this->lastModified = $lastModified;
        $this->slides = $slides;
        $this->video = $video;
    }*/
}
