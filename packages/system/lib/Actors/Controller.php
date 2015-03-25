<?php

namespace Embark\CMS\Actors;

use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\ClosureFilterIterator;
use DirectoryIterator;
use DOMDocument;

class Controller {
    const FILE_EXTENSION = '.xml';

    /**
     * Locate an actor.
     */
    public static function locate($name)
    {
        // Does not have a file extension, assume it is a handle
        if (false === strpos($name, static::FILE_EXTENSION)) {
            $name = ACTORS . '/' . basename($name) . static::FILE_EXTENSION;
        }

        if (is_file($name)) {
            return $name;
        }

        return false;
    }

    public static function findAll()
    {
        $iterator = new ClosureFilterIterator(new DirectoryIterator(ACTORS), function($item) {
            return (
                false === $item->isDir()
                && false !== strpos($item->getFilename(), static::FILE_EXTENSION)
            );
        });

        foreach ($iterator as $item) {
            $actor = static::read($item->getPathname());

            if (false === $actor) continue;

            yield $actor['resource']['handle'] => $actor;
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

    public static function toXML(MetadataInterface $actor)
    {
        $document = new DOMDocument();
        $root = $document->createElement('object');
        $root->setAttribute('type', get_class($actor));
        $document->appendChild($root);
        $actor->toXML($root);

        return $document;
    }

    public static function write(MetadataInterface $actor)
    {
        $file = $actor['resource']['uri'];
        $document = static::toXML($actor);
        $document->formatOutput = true;

        return $document->save($file);
    }
}