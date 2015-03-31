<?php

namespace Embark\CMS\Structures;

use Embark\CMS\Structures\MetadataInterface;

class Enum implements MetadataValueInterface
{
    protected $values;

    public function __construct(array $values)
    {
        $this->values = $values;
    }

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
        if (in_array($value, $this->values)) {
            return $value;
        }
    }
}
