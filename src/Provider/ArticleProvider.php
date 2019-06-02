<?php

namespace App\Provider;

use App\Model\Article;
use Content\Behaviour\ContentProviderInterface;

class ArticleProvider implements ContentProviderInterface
{
    public function getDirectory(): string
    {
        return 'article';
    }

    public function supports(string $className): bool
    {
        return is_a($className, Article::class, true);
    }
}
