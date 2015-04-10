<?php

namespace Embark\CMS\Fields;

use Embark\CMS\Metadata\MetadataControllerInterface;
use Embark\CMS\Metadata\MetadataControllerTrait;

class Controller implements MetadataControllerInterface
{
    use MetadataControllerTrait;

    const DIR = '/data/fields';
    const FILE_EXTENSION = '.xml';
}
