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
    public string $slides;
    public string $sources;
    public ?string $video = null;

    public function getUrl(): ?string
    {
        if ($this->video !== null) {
            return $this->video;
        }

        return $this->slides;
    }
}
