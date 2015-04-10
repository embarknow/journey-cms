<?php

namespace Embark\CMS\Metadata;

/**
 * Interface to define a metadata value that needs sanitizing
 */
interface SanitizedMetadataValueInterface
{
    /**
     * Sanitizes a metadata value
     *
     * @param  mixed $value
     *  mixed type value to be sanitized
     *
     * @return string
     *  sanitized string value
     */
    public function sanitize($value);

    /**
     * Reverse sanitizes a metadata value
     *
     * @param  string $value
     *  string value to be sanitized
     *
     * @return mixed
     *  sanitized mixed type value
     */
    public function reverseSanitize($value);
}
