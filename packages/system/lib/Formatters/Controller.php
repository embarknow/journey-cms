<?php

namespace Embark\CMS\Formatters;

use Embark\CMS\Metadata\MetadataControllerInterface;
use Embark\CMS\Metadata\MetadataControllerTrait;

class Controller implements MetadataControllerInterface
{
    use MetadataControllerTrait;

    const DIR = '/data/formatters';
    const FILE_EXTENSION = '.xml';
}
