<?php

namespace Embark\Journey\Metadata\Filters;

use Embark\CMS\Metadata\MetadataValueInterface;
use Embark\CMS\Metadata\SanitizedMetadataValueInterface;

/**
 * Implements MetadataValueInterface for an zerofilled integer metadata value
 */
class ZerofillInteger implements MetadataValueInterface, SanitizedMetadataValueInterface
{
    /**
     * Number of places to zerofill
     * @var int
     */
    protected $zerofill;

    /**
     * Accepts the number of places to zerofill
     *
     * @param int $zerofill
     *  the number of places to zerofill
     */
    public function __construct($zerofill)
    {
        $this->zerofill = $zerofill;
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
    public function sanitize($value)
    {
        return str_pad($value, $this->zerofill, '0', STR_PAD_LEFT);
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
