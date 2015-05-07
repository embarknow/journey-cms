<?php

namespace Embark\CMS\Sections;

use Embark\CMS\Metadata\MetadataControllerTrait;
use Embark\CMS\Metadata\MetadataInterface;

class Controller
{
    use MetadataControllerTrait;

    protected static $directory = '/blueprints/sections';
    protected static $extension = '.xml';
}
