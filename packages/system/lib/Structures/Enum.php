<?php

namespace Embark\CMS\Structures;

use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\SanitizedMetadataValueInterface;

/**
 * Implements MetadataValueInterface for a enum metadata value
 */
class Enum implements MetadataValueInterface, SanitizedMetadataValueInterface
{
    /**
     * Array of possible enum values for this instance
     * @var array
     */
    protected $values;

    /**
     * Accepts an array of possible enum values for this instance
     *
     * @param array $values
     *  array of possible enum values for this instance
     */
    public function __construct(array $values)
    {
        $this->values = $values;
    }

    /**
     * Set an enum value to XML
     *
     * @param  mixed $value
     *  the string value to set
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
     *
     * @return mixed
     *  the mixed type value
     */
    public function fromXML($value)
    {
        return $this->reverseSanitize($value);
    }

    /**
     * Sanitizes a metadata value by chaecking it is in the possible values for this type
     *
     * @param  string $value
     *  string value to be sanitized
     *
     * @return mixed
     *  sanitized mixed type value
     */
    public function sanitize($value)
    {
        if (in_array($value, $this->values)) {
            return $value;
        }
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
