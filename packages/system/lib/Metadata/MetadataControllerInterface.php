<?php

namespace Embark\CMS\Metadata;

use DOMDocument;
use Embark\CMS\Metadata\MetadataInterface;

/**
 * Interface to define the use of metadata files to create class structures describing data
 */
interface MetadataControllerInterface
{
    /**
     * Locate an object.
     *
     * @param   string              $handleOrFile
     *  If a handle is provided the object will be loaded from
     *  the default location, if a file name is provided that
     *  will be used instead.
     *
     * @uses  DOCROOT
     *  to locate the file from the document root
     * @uses static::FILE_EXTENSION
     *  to define the file extension
     * @uses static::DIR
     *  to locate the file in a directory
     *
     * @return  string|false
     *  The file name of the object or false on failure.
     */
    public static function locate($handleOrFile);

    /**
     * Save a new object.
     *
     * @param   MetadataInterface   $object
     * @param   string              $handleOrFile
     *  If a handle is provided the object will be saved to
     *  the default location, if a file name is provided that
     *  will be used instead.
     *
     * @uses  DOCROOT
     *  to locate the file from the document root
     * @uses static::FILE_EXTENSION
     *  to define the file extension
     * @uses static::DIR
     *  to locate the file in a directory
     *
     * @return int|false
     *  Number of bytes written or false if an error occurred
     */
    public static function create(MetadataInterface $object, $handleOrFile);

    /**
     * Read an object.
     *
     * @param   string              $handleOrFile
     *  If a handle is provided the object will be loaded from
     *  the default location, if a file name is provided that
     *  will be used instead.
     *
     * @return object
     *  Class structure defined by the metadata document
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
     *
     * @uses  DOCROOT
     *  to locate the file from the document root
     * @uses static::FILE_EXTENSION
     *  to define the file extension
     * @uses static::DIR
     *  to locate the file in a directory
     *
     * @return int|boolean
     *  Number of bytes written, true if the file was deleted, or false if an error occured
     */
    public static function update(MetadataInterface $object, $handleOrFile = null);

    /**
     * Delete an object.
     *
     * @param   MetadataInterface   $object
     *
     * @uses  DOCROOT
     *  to locate the file from the document root
     *
     * @return boolean
     *  True on success or False on failure, from unlink
     */
    public static function delete(MetadataInterface $object);

    /**
     * Get metadata from XML
     *
     * @param  DOMDocument $document
     *  A document to create a class structure from describing metadata
     *
     * @return object
     *  Class structure defined by the metadata document
     */
    public static function fromXML(DOMDocument $document);

    /**
     * Save metadata to DOMDocument XML
     *
     * @param  MetadataInterface $object
     *
     * @return DOMDocument
     *  instance of DOMDocument containing metadata as XML structure
     */
    public static function toXML(MetadataInterface $object);
}
