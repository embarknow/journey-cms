<?php

namespace Embark\CMS\Relationships;

use Embark\CMS\Metadata\MetadataControllerTrait;

class Controller implements MetadataControllerInterface, SyncableControllerInterface
{
    use MetadataControllerTrait {
        MetadataControllerTrait::delete as deleteFile;
    }

    const DIR = '/workspace/relationships';
    const FILE_EXTENSION = '.xml';

    public static function delete(MetadataInterface $object)
    {

    }

    public static function sync(MetadataInterface $object)
    {

    }
}
