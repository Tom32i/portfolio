<?php

namespace Content\PropertyHandler;

use Content\Behaviour\PropertyHandlerInterface;

/**
 * Parse the given property as Datetime
 */
class DateTimePropertyHandler implements PropertyHandlerInterface
{
    /**
     * Is data supported?
     *
     * @param array $data
     *
     * @return boolean
     */
    public function isSupported($value): bool
    {
        try {
            new \DateTime($value);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function handle($value, array $context)
    {
        return new \DateTime($value);
    }
}
