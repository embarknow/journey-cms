<?php

class Parameter
{
    public $value;
    public $key;

    public function __construct($key, $value)
    {
        $this->value = $value;
        $this->key = $key;
    }

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