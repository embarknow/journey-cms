<?php

namespace Embark\CMS\Metadata;

use ArrayAccess;
use DOMElement;

/**
 * Interface to define the use of metadata files to create class structures describing data
 */
interface MetadataInterface extends ArrayAccess
{
    /**
     * Set defaults defined in the schema array
     *
     * @return void
     */
    public function setDefaults();

    /**
     * Get metadata from XML
     *
     * @param  DOMElement  $xml
     *  An element to create a class structure from describing metadata
     *
     * @return void
     */
    public function fromXML(DOMElement $xml);

    /**
     * Save metadata to an element
     *
     * @param  DOMElement $xml
     *  an element to save to
     *
     * @return void
     */
    public function toXML(DOMElement $xml);
}
