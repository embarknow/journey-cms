<?php

namespace Embark\CMS\Structures;

class Integer implements MetadataValueInterface
{
    public function toXML($value)
    {
        return $this->sanitise($value);
    }

    public function fromXML($value)
    {
        return $this->sanitise($value);
    }

    public function sanitise($value)
    {
        return (integer)$value;
    }
}
