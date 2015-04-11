<?php

namespace Embark\CMS\Actors;

use Embark\CMS\Metadata\MetadataControllerInterface;
use Embark\CMS\Metadata\MetadataControllerTrait;

class Controller implements MetadataControllerInterface
{
    use MetadataControllerTrait;

    const DIR = '/workspace/actors';
    const FILE_EXTENSION = '.xml';
}
