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

    public function getSiteName(): string
    {
        return trim(preg_replace('/^https?:\/\/(.+)$/', '$1', $this->url), '/');
    }
}
