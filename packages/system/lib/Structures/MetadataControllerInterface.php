<?php

namespace Embark\CMS\Structures;

use DOMDocument;
use Embark\CMS\Structures\MetadataInterface;

interface MetadataControllerInterface
{
    /**
     * Save a new object.
     *
     * @param   MetadataInterface   $object
     * @param   string              $handleOrFile
     *  If a handle is provided the object will be saved to
     *  the default location, if a file name is provided that
     *  will be used instead.
     */
    public static function create(MetadataInterface $object, $handleOrFile);

    /**
     * Locate an object.
     *
     * @param   string              $handleOrFile
     *  If a handle is provided the object will be loaded from
     *  the default location, if a file name is provided that
     *  will be used instead.
     *
     * @return  string|false
     *  The file name of the object or false on failure.
     */
    public static function locate($handleOrFile);

    /**
     * Read an object.
     *
     * @param   string              $handleOrFile
     *  If a handle is provided the object will be loaded from
     *  the default location, if a file name is provided that
     *  will be used instead.
     */
    public static function read($handleOrFile);

    /**
     * Update an object.
     *
     * @param   MetadataInterface   $object
     * @param   string              $handleOrFile
     *  If a handle is provided the object will be saved to
     *  the default location, if a file name is provided that
     *  will be used instead.
     */
    public static function update(MetadataInterface $object, $handleOrFile = null);

    /**
     * Delete an object.
     *
     * @param   MetadataInterface   $object
     */
    public static function delete(MetadataInterface $object);

    /**
     * Get metadata from XML
     * @param  DOMDocument $document
     */
    public static function fromXML(DOMDocument $document);

    /**
     * Set metadata to XML
     * @param  MetadataInterface $object
     */
    public static function toXML(MetadataInterface $object);
}
