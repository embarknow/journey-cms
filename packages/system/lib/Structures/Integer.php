<?php

namespace Embark\CMS\Structures;

use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\SanitizedMetadataInterface;

/**
 * Implements MetadataValueInterface for an integer metadata value
 */
class Integer implements MetadataValueInterface, SanitizedMetadataInterface
{
    /**
     * Set an integer value to XML
     *
     * @param  int $value
     *  the integer value to set
     *
     * @return string
     *  the value as a string for XML
     */
    public function toXML($value)
    {
        return $this->sanitize($value);
    }

    /**
     * Get a value from XML
     *
     * @param  string $value
     *  the value as a string from XML
     * @return mixed
     *  the mixed type value
     */
    public function fromXML($value)
    {
        return $this->reverseSanitize($value);
    }

    /**
     * Sanitizes a metadata value
     *
     * @param  string $value
     *  string value to be sanitized
     *
     * @return mixed
     *  sanitized mixed type value
     */
    public function sanitize($value)
    {
        return (string) $value;
    }

    /**
     * Reverse sanitizes a metadata value
     *
     * @param  string $value
     *  string value to be sanitized
     *
     * @return mixed
     *  sanitized mixed type value
     */
    public function reverseSanitize($value)
    {
        return (integer) $value;
    }
}
