<?php

namespace Embark\CMS\Structures;

/**
 * A parameter key and value for xslt
 */
class Parameter
{
    /**
     * Parameter key
     *
     * @var string
     */
    public $key;

    /**
     * Parameter value
     *
     * @var mixed
     */
    public $value;

    /**
     * Accepts a key and value for this parameter
     *
     * @param string $key
     *  parameter key as a string
     *
     * @param mixed $value
     *  primitive value that can be cast to a string
     */
    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    /**
     * Cast the parameter value to a string
     *
     * @return string
     *  the value as a string
     */
    public function __toString()
    {
        if (is_array($this->value)) {
            return implode(',', $this->value);
        }

        return (
            !is_null($this->value)
                ? (string)$this->value
                : ''
        );
    }
}
