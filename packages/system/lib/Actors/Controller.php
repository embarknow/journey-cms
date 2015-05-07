<?php

namespace Embark\CMS\Actors;

use Embark\CMS\Metadata\MetadataControllerInterface;
use Embark\CMS\Metadata\MetadataControllerTrait;

class Controller implements MetadataControllerInterface
{
    use MetadataControllerTrait;

    protected static $directory = '/blueprints/actors';
    protected static $extension = '.xml';
}
