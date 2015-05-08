<?php

namespace Embark\CMS\Configuration;

use DirectoryIterator;
use Embark\CMS\Metadata\MetadataControllerInterface;
use Embark\CMS\Metadata\MetadataControllerTrait;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\ClosureFilterIterator;

class Controller implements MetadataControllerInterface
{
    use MetadataControllerTrait;

    protected static $directory = '/app/config';
    protected static $extension = '.xml';

    /**
     * Environment name variable
     * @var string
     */
    protected static $environment = 'default';

    /**
     * Set the environment variable
     *
     * @param  string $environment
     *  the environment to use
     */
    public static function environment($environment = null)
    {
        if (null === $environment) {
            return static::$environment;
        }

        static::$environment = $environment;
    }

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
     * @uses static::$extension
     *  to define the file extension
     * @uses static::$directory
     *  to locate the file in a directory
     *
     * @return  string|false
     *  The file name of the object or false on failure.
     */
    public static function locate($handleOrFile)
    {
        // Does not have a file extension, assume it is a handle
        if (false === strpos($handleOrFile, static::$extension)) {
            $handleOrFile = realpath(DOCROOT . static::$directory . '/' . static::$environment . '/' . basename($handleOrFile) . static::$extension);
        }

        if (is_file($handleOrFile)) {
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
     * @uses static::$extension
     *  to define the file extension
     * @uses static::$directory
     *  to locate the file in a directory
     *
     * @return int|false
     *  Number of bytes written or false if an error occurred
     */
    public static function create(MetadataInterface $object, $handleOrFile)
    {
        $file = $handleOrFile;

        // Does not have a file extension, assume it is a handle
        if (false === strpos($handleOrFile, static::$extension)) {
            if (!file_exists(DOCROOT . static::$directory . '/' . static::$environment)) {
                mkdir(DOCROOT . static::$directory . '/' . static::$environment, 0755, true);
            }

            $file = DOCROOT . static::$directory . '/' . static::$environment . '/' . basename($handleOrFile) . static::$extension;
        }

        $document = static::toXML($object);
        $document->formatOutput = true;

        return $document->save($file);
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
     * @uses static::$extension
     *  to define the file extension
     * @uses static::$directory
     *  to locate the file in a directory
     *
     * @return int|boolean
     *  Number of bytes written, true if the file was deleted, or false if an error occured
     */
    public static function update(MetadataInterface $object, $handleOrFile = null)
    {
        // Does not have a file extension, assume it is a handle
        if (isset($handleOrFile) && false === strpos($handleOrFile, static::$extension)) {
            $file = DOCROOT . static::$directory . '/' . static::$environment . '/' . basename($handleOrFile) . static::$extension;
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
     * Find all metadata in a directory
     *
     * @uses  DOCROOT
     *  to locate the file from the document root
     * @uses static::$extension
     *  to define the file extension
     * @uses static::$directory
     *  to locate the file in a directory
     *
     * @return array
     *  Yields an iteratable array of key => value pairs
     */
    public static function findAll()
    {
        $iterator = new ClosureFilterIterator(new DirectoryIterator(DOCROOT . static::$directory . '/' . static::$environment), function ($item) {
            return (
                false === $item->isDir()
                && false !== strpos($item->getFilename(), static::$extension)
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
}
