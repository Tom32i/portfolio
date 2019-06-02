<?php

namespace Content\Builder;

/**
 * Asset List
 */
class AssetList implements \Iterator, \ArrayAccess
{
    private $assets = [];

    public function rewind()
    {
        return \reset($this->assets);
    }

    public function current()
    {
        return \current($this->assets);
    }

    public function key()
    {
        return \key($this->assets);
    }

    public function next()
    {
        return \next($this->assets);
    }

    public function valid()
    {
        return key($this->assets) !== null;
    }

    public function offsetSet($offset, $value)
    {
        $this->assets[] = $value;
    }

    public function offsetExists($offset)
    {
        return isset($this->assets[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->assets[$offset]);
    }

    public function offsetGet($offset) {
        return $this->assets[$offset] ?? null;
    }
}

