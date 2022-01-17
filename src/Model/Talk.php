<?php

declare(strict_types=1);

namespace App\Model;

class Talk
{
    public string $title;
    public string $description;
    public string $slug;
    public int $duration;
    public \DateTimeImmutable $date;
    public \DateTimeImmutable $lastModified;
    public ?string $slides = null;
    public ?string $video = null;

    public function getUrl(): string
    {
        if ($this->video) {
            return $this->video;
        }

        return $this->slides;
    }
}
