<?php

namespace Embark\CMS\Sections;

use Embark\CMS\Metadata\MetadataControllerTrait;
use Embark\CMS\Metadata\MetadataInterface;

class Controller
{
    use MetadataControllerTrait;

    const DIR = '/workspace/sections';
    const FILE_EXTENSION = '.xml';
}
