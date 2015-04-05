<?php

namespace Embark\CMS\Structures;

use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\SanitizedMetadataValueInterface;

/**
 * Implements MetadataValueInterface for a maximum tested integer metadata value
 */
class MaxInteger implements MetadataValueInterface, SanitizedMetadataValueInterface
{
    /**
     * Default max value for this instance
     * @var integer
     */
    protected $max;

    /**
     * Accepts a default max value for this instance
     * @param int $max
     *  intger value for the default
     */
    public function __construct($max)
    {
        $this->max = (integer) $max;
    }

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
    public function sanitise($value)
    {
        return (string) max($this->max, (integer) $value);
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
        return max($this->max, (integer) $value);
    }
}
