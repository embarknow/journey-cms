<?php

namespace Embark\CMS\Structures;

use Embark\CMS\ClosureFilterIterator;
use DirectoryIterator;
use DOMDocument;

trait MetadataControllerTrait
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
     * Read an object.
     *
     * @param   string              $handleOrFile
     *  If a handle is provided the object will be loaded from
     *  the default location, if a file name is provided that
     *  will be used instead.
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
            $file = $object['resource']['uri'];
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
            && $file !== $object['resource']['uri']
        ) {
            return unlink($object['resource']['uri']);
        }

        return $result;
    }

    /**
     * Delete an object.
     *
     * @param   MetadataInterface   $object
     */
    public static function delete(MetadataInterface $object)
    {
        $file = $object['resource']['uri'];

        return unlink($file);
    }

    /**
     * Shortcut for read
     *
     * @param   string              $handleOrFile
     *  If a handle is provided the object will be loaded from
     *  the default location, if a file name is provided that
     *  will be used instead.
     */
    public static function find($handleOrFile)
    {
        return static::read($handleOrFile);
    }

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
     * Get metadata from XML
     * @param  DOMDocument $document
     */
    public static function fromXML(DOMDocument $document)
    {
        $element = $document->documentElement;
        $type = '\\' . $element->getAttribute('type');
        $metadata = new $type;
        $metadata->fromXML($element);
        $metadata->setDefaults();

        return $metadata;
    }

    /**
     * Set metadata to XML
     * @param  MetadataInterface $object
     */
    public static function toXML(MetadataInterface $object)
    {
        $document = new DOMDocument();
        $root = $document->createElement('object');
        $root->setAttribute('type', get_class($object));
        $document->appendChild($root);
        $object->toXML($root);

        return $document;
    }
}