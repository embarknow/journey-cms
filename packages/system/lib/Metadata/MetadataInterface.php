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
     * Set the schema definition for this metadata
     *
     * @param array $schema
     *  array describing the expected schema definition that a metadata file describes
     */
    public function setSchema(array $schema);

    /**
     * Gets an index of referenced Metadata
     *
     * @return MetadataReferenceIndex
     *  an index of referenced Metadata
     */
    public function getReferenceIndex();

    /**
     * Gets metadata from a MetadataInterface instance and adds it to a store
     *
     * @param  MetadataInterface $object
     *  instance to get the data from
     *
     * @return void
     */
    public function fromMetadata(MetadataInterface $object);

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

    /**
     * Find all metadata
     *
     * @return Generator
     *  yeilds a generator of key => value pairs
     */
    public function findAll();

    /**
     * Find instances of a certian class
     *
     * @param  mixed $class
     *  the class to find instances of
     *
     * @return Generator
     *  yeilds a generator of instances
     */
    public function findInstancesOf($class);

    /**
     * Resolve this instance
     *
     * @return self
     */
    public function resolve();

    /**
     * Resolve instances of a class to this instance
     *
     * @param  mixed $class
     *  the class to resolve
     *
     * @return self
     *  returns self if resolved
     *
     * @throws Exception
     *  if an instance of the class cannot be resolved to this instance
     */
    public function resolveInstanceOf($class);
}
