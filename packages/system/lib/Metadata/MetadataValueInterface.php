<?php

namespace Embark\CMS\Metadata;

/**
 * Interface to define a metadata value
 */
interface MetadataValueInterface
{
    /**
     * Set a value to XML
     *
     * @param  mixed $value
     *  the mixed value to set
     *
     * @return string
     *  the value as a string for XML
     */
    public function toXML($value);

    /**
     * Get a value from XML
     *
     * @param  string $value
     *  the value as a string from XML
     * @return mixed
     *  the mixed type value
     */
    public function fromXML($value);
}
