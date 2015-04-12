<?php

namespace Embark\CMS\Views\Public;

use Embark\CMS\Metadata\MetadataControllerInterface;
use Embark\CMS\Metadata\MetadataControllerTrait;

class Controller implements MetadataControllerInterface
{
    use MetadataControllerTrait;

    const DIR = '/blueprints/views/public';
    const FILE_EXTENSION = '.xml';
}
