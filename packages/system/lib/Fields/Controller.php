<?php

namespace Embark\CMS\Fields;

use Embark\CMS\Structures\MetadataControllerInterface;
use Embark\CMS\Structures\MetadataControllerTrait;

class Controller implements MetadataControllerInterface
{
    use MetadataControllerTrait;

    const DIR = '/data/fields';
    const FILE_EXTENSION = '.xml';
}