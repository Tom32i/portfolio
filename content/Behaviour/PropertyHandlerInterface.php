<?php

namespace Content\Behaviour;

use Content\Behaviour\PropertyHandlerInterface;

/**
 * Property Handler interface
 */
interface PropertyHandlerInterface
{
    /**
     * Is data supported?
     *
     * @param mixed $value The property value
     *
     * @return boolean
     */
    public function isSupported($value): bool;

    /**
     * Handle property
     *
     * @param mixed $value The property value
     * @param array $context The context of parsing process
     *
     * @return mixed
     */
    public function handle($value, array $context);
}
