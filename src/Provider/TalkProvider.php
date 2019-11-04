<?php

namespace App\Provider;

use App\Model\Talk;
use Content\Behaviour\ContentProviderInterface;

class TalkProvider implements ContentProviderInterface
{
    public function getDirectory(): string
    {
        return 'talk';
    }

    public function supports(string $className): bool
    {
        return is_a($className, Talk::class, true);
    }
}
