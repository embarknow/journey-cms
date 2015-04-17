<?php

namespace Embark\Journey\Metadata\Filters;

use Embark\CMS\Metadata\MetadataValueInterface;
use Embark\CMS\Metadata\SanitizedMetadataValueInterface;

/**
 * Implements MetadataValueInterface for a semver string
 */
class Semver implements MetadataValueInterface, SanitizedMetadataValueInterface
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
        // need to implement semver code here
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
        // need to implement semver code here
        return (string) $value;
    }
}
