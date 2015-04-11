<?php

namespace Embark\CMS\Metadata;

use ArrayAccess;
use DirectoryIterator;
use DOMDocument;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\ReferencedMetadataInterface;
use Embark\CMS\ClosureFilterIterator;

/**
 * Trait implementing MetadataControllerInterface
 *
 * @see MetadataControllerInterface
 */
trait MetadataControllerTrait
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
    public static function locate($handleOrFile)
    {
        // Does not have a file extension, assume it is a handle
        if (false === strpos($handleOrFile, static::FILE_EXTENSION)) {
            return DOCROOT . static::DIR . '/' . basename($handleOrFile) . static::FILE_EXTENSION;
        }

        else if (is_file($handleOrFile)) {
           return $handleOrFile;
        }

        else {
            return false;
        }
    }

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
    public static function create(MetadataInterface $object, $handleOrFile)
    {
        $file = $handleOrFile;

        // Does not have a file extension, assume it is a handle
        if (false === strpos($handleOrFile, static::FILE_EXTENSION)) {
            $file = DOCROOT . static::DIR . '/' . basename($handleOrFile) . static::FILE_EXTENSION;
        }

        $document = static::toXML($object);
        $document->formatOutput = true;

        return $document->save($file);
    }

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
    public static function read($handleOrFile)
    {
        $file = static::locate($handleOrFile);

        if ($file) {
            $document = new DOMDocument();
            $document->load($file);

            return static::fromXML($document);
        }

        return false;
    }

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
    public static function update(MetadataInterface $object, $handleOrFile = null)
    {
        // Does not have a file extension, assume it is a handle
        if (isset($handleOrFile) && false === strpos($handleOrFile, static::FILE_EXTENSION)) {
            $file = DOCROOT . static::DIR . '/' . basename($handleOrFile) . static::FILE_EXTENSION;
        }

        // The handle is a file and it exists:
        else if (isset($handleOrFile) && is_file($handleOrFile)) {
            $file = $handleOrFile;
        }

        // Use the stored file name:
        else if (isset($object['resource']['uri'])) {
            $file = DOCROOT . '/' . $object['resource']['uri'];
        }

        // No file name to be had:
        else {
            return false;
        }

        $document = static::toXML($object);
        $document->formatOutput = true;

        $result = $document->save($file);

        // The file name has changed:
        if (
            $result
            && isset($object['resource']['uri'])
            && $file !== DOCROOT . '/' . $object['resource']['uri']
        ) {
            return unlink(DOCROOT . '/' . $object['resource']['uri']);
        }

        return $result;
    }

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
    public static function delete(MetadataInterface $object)
    {
        $file = DOCROOT . '/' . $object['resource']['uri'];

        return unlink($file);
    }

    /**
     * Alias for read
     *
     * @see static::read
     */
    public static function find($handleOrFile)
    {
        return static::read($handleOrFile);
    }

    /**
     * Find all metadata in a directory
     *
     * @uses  DOCROOT
     *  to locate the file from the document root
     * @uses static::FILE_EXTENSION
     *  to define the file extension
     * @uses static::DIR
     *  to locate the file in a directory
     *
     * @return array
     *  Yields an iteratable array of key => value pairs
     */
    public static function findAll()
    {
        $iterator = new ClosureFilterIterator(new DirectoryIterator(DOCROOT . static::DIR), function ($item) {
            return (
                false === $item->isDir()
                && false !== strpos($item->getFilename(), static::FILE_EXTENSION)
            );
        });

        foreach ($iterator as $item) {
            $object = static::read($item->getPathname());

            if (false === $object) {
                continue;
            }

            yield $object['resource']['handle'] => $object;
        }
    }

    /**
     * Make sure all metadata are loaded so any references can be followed.
     */
    public static function loadAll()
    {
        foreach (static::findAll() as $schema) {
        }
    }

    /**
     * Get metadata from XML
     *
     * @param  DOMDocument $document
     *  A document to create a class structure from describing metadata
     *
     * @return object
     *  Class structure defined by the metadata document
     */
    public static function fromXML(DOMDocument $document)
    {
        $element = $document->documentElement;
        $type = '\\' . $element->getAttribute('type');
        $metadata = new $type;
        $metadata->fromXML($element);
        $metadata->setDefaults();

        if ($metadata instanceof ReferencedMetadataInterface) {
            $metadata->setGuid($element->getAttribute('guid'));
        }

        return $metadata;
    }

    /**
     * Save metadata to DOMDocument XML
     *
     * @param  MetadataInterface $object
     *
     * @return DOMDocument
     *  instance of DOMDocument containing metadata as XML structure
     */
    public static function toXML(MetadataInterface $object)
    {
        $document = new DOMDocument();
        $root = $document->createElement('object');
        $root->setAttribute('type', get_class($object));
        $document->appendChild($root);
        $object->toXML($root);

        if ($object instanceof ReferencedMetadataInterface) {
            $root->setAttribute('guid', $object->getGuid());
        }

        return $document;
    }
}
