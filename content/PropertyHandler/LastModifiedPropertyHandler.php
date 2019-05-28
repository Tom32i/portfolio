<?php

namespace Content\PropertyHandler;

use Content\Behaviour\PropertyHandlerInterface;

/**
 * Set a "LastModified" property based on file date
 */
class LastModifiedPropertyHandler implements PropertyHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function isSupported($value): bool
    {
        return !$value;
    }

    /**
     * {@inheritdoc}
     */
    public function handle($value, array $context)
    {
        $lastModified = new \DateTime();
        $lastModified->setTimestamp($context['file']->getMTime());

        return $lastModified;
    }
}
