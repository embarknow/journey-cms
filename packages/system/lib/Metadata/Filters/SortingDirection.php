<?php

namespace Embark\CMS\Metadata\Filters;

use Embark\CMS\Metadata\MetadataValueInterface;
use Embark\CMS\Metadata\SanitizedMetadataValueInterface;

class SortingDirection implements MetadataValueInterface, SanitizedMetadataValueInterface
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
        return $this->sanitise($value);
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
        return $this->sanitise($value);
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
        switch (strtolower($value)) {
            case 'rand':
            case 'random':
                return 'random';

            case 'desc':
            case 'descending':
                return 'desc';

            case 'asc':
            case 'ascending':
                return 'asc';

            default:
                return $this->default;
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
        return $this->sanitise($value);
    }
}
