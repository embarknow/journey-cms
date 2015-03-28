<?php

namespace Embark\CMS\Actors;

use Embark\CMS\Structures\MetadataControllerInterface;
use Embark\CMS\Structures\MetadataControllerTrait;

class Controller implements MetadataControllerInterface
{
    use MetadataControllerTrait;

    const DIR = '/workspace/actors';
    const FILE_EXTENSION = '.xml';
}
