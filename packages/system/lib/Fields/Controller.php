<?php

namespace Embark\CMS\Fields;

use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\ClosureFilterIterator;
use DirectoryIterator;
use DOMDocument;

class Controller {
    const DIR = DATA . '/fields';
    const FILE_EXTENSION = '.xml';

    /**
     * Locate a field.
     */
    public static function locate($name)
    {
        // Does not have a file extension, assume it is a handle
        if (false === strpos($name, static::FILE_EXTENSION)) {
            $name = self::DIR . '/' . basename($name) . static::FILE_EXTENSION;
        }

        if (is_file($name)) {
            return $name;
        }

        return false;
    }

    public static function findAll()
    {
        $iterator = new ClosureFilterIterator(new DirectoryIterator(self::DIR), function($item) {
            return (
                false === $item->isDir()
                && false !== strpos($item->getFilename(), static::FILE_EXTENSION)
            );
        });

        foreach ($iterator as $item) {
            $field = static::read($item->getPathname());

            if (false === $field) continue;

            yield $field['resource']['handle'] => $field;
        }
    }

    public static function fromXML(DOMDocument $document)
    {
        $element = $document->documentElement;
        $type = '\\' . $element->getAttribute('type');
        $metadata = new $type;
        $metadata->fromXML($element);
        $metadata->setDefaults();

        return $metadata;
    }

    public static function read($name)
    {
        $file = static::locate($name);

        if (false === $file) return false;

        $document = new DOMDocument();
        $document->load($file);

        return static::fromXML($document);
    }

    public static function toXML(MetadataInterface $field)
    {
        $document = new DOMDocument();
        $root = $document->createElement('object');
        $root->setAttribute('type', get_class($field));
        $document->appendChild($root);
        $field->toXML($root);

        return $document;
    }

    public static function write(MetadataInterface $field)
    {
        $file = $field['resource']['uri'];
        $document = static::toXML($field);
        $document->formatOutput = true;

        return $document->save($file);
    }
}