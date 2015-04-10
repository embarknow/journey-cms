<?php

namespace Embark\CMS\Structures;

use Embark\CMS\Metadata\MetadataInterface;

/**
 * Implements MetadataValueInterface for a boolean metadata value
 */
class Boolean implements MetadataValueInterface
{
    /**
     * Set a boolean value to XML
     *
     * @param  boolean $value
     *  the boolean value to set
     *
     * @return string
     *  the value as a string for XML
     */
    public function toXML($value)
    {
        return ($value ? 'yes' : 'no');
    }

    /**
     * Get a boolean value from XML
     *
     * @param  string $value
     *  the value as a string from XML
     * @return boolean
     *  the boolean value
     */
    public function fromXML($value)
    {
        return ($value === 'yes');
    }
}
