<?php

declare(strict_types=1);

namespace App\Model;

class Tool
{
    public string $slug;
    public string $title;
    public string $description;
    public string $url;
    /** Package name on npm, when published there */
    public ?string $npm = null;
    /** Package name on Packagist, when published there */
    public ?string $packagist = null;
    public \DateTimeImmutable $lastModified;
    public int $stars;
    /** Downloads over the last year, from the package registry */
    public ?int $downloads = null;
    public int $priority;
}
