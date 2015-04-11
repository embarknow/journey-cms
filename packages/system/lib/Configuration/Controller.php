<?php

namespace Embark\CMS\Configuration;

use Embark\CMS\Metadata\MetadataControllerInterface;
use Embark\CMS\Metadata\MetadataControllerTrait;

class Controller implements MetadataControllerInterface
{
    use MetadataControllerTrait;

    const DIR = '/manifest/config';
    const FILE_EXTENSION = '.xml';
}
