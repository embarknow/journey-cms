<?php

namespace Embark\CMS\Formatters;

use Embark\CMS\Metadata\MetadataControllerInterface;
use Embark\CMS\Metadata\MetadataControllerTrait;

class Controller implements MetadataControllerInterface
{
    use MetadataControllerTrait;

    protected static $directory = '/data/formatters';
    protected static $extension = '.xml';
}
