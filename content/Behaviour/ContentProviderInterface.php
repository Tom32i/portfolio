<?php

namespace Content\Behaviour;

interface ContentProviderInterface
{
    public function supports(string $className): bool;
}
