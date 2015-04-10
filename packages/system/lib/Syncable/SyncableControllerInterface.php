<?php

namespace Embark\CMS\Syncable;

use Embark\CMS\Metadata\MetadataInterface;

/**
 * Syncable Controller Interface
 * Defines a class that can synchronise XML metadata to the database cache
 */
interface SyncableControllerInterface
{
    /**
     * Perform a synchronisation of metadata to the database
     * @param  MetadataInterface $object
     * @return void
     */
    public static function sync(MetadataInterface $object);
}
