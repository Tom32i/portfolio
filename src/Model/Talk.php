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
}
