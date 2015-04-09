<?php

namespace Embark\CMS\Structures;

use ArrayAccess;

class MetadataReferenceIndex implements ArrayAccess
{
    protected $index;

    public function __construct()
    {
        $this->index = [];
    }

    /**
     * @see ArrayAccess
     */
    public function offsetExists($name)
    {
        return isset($this->index[$name]);
    }

    /**
     * @see ArrayAccess
     */
    public function offsetGet($name)
    {
        if (false === isset($this->index[$name])) {
            return null;
        }

        return $this->index[$name];
    }

    /**
     * @see ArrayAccess
     */
    public function offsetSet($name, $value)
    {
        return $this->index[$name] = $value;
    }

    /**
     * @see ArrayAccess
     */
    public function offsetUnset($name)
    {
        unset($this->index[$name]);
    }
}