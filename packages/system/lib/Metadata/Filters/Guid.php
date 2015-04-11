<?php

namespace Embark\CMS\Metadata\Filters;

use Embark\CMS\Metadata\MetadataValueInterface;
use Embark\CMS\Metadata\SanitizedMetadataValueInterface;

/**
 * Implements MetadataValueInterface for a unique GUID metadata value
 */
class Guid implements MetadataValueInterface, SanitizedMetadataValueInterface
{
    protected static $refs;

    /**
     * Set a GUID value to XML
     *
     * @param  string $value
     *  the GUID value to set
     *
     * @return string
     *  the GUID value as a string for XML
     */
    public function toXML($value)
    {
        return $this->sanitize($value);
    }

    /**
     * Get a GUID value from XML
     *
     * @param  string $value
     *  the GUID value as a string from XML
     * @return mixed
     *  the GUID string value
     */
    public function fromXML($value)
    {
        return $this->sanitize($value);
    }

    /**
     * Sanitizes a GUID value and returns a default if absent
     *
     * @param  string|false|null $value
     *  string GUID value to be sanitized or false|null for absent value
     *
     * @return mixed
     *  sanitized mixed type value
     */
    public function sanitize($value)
    {
        return (
            isset($value)
                ? $value
                : uniqid()
        );
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
        return $this->sanitize($value);
    }
}
