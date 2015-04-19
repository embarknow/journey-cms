<?php

namespace Embark\Journey\Metadata\Routes;

use DirectoryIterator;
use Embark\CMS\Metadata\MetadataControllerInterface;
use Embark\CMS\Metadata\MetadataControllerTrait;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\ClosureFilterIterator;

class Controller implements MetadataControllerInterface
{
    use MetadataControllerTrait;

    const DIR = '/app/routes';
    const FILE_EXTENSION = '.xml';
}
