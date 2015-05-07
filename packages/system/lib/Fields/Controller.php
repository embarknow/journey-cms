<?php

namespace Embark\CMS\Fields;

use Embark\CMS\Metadata\MetadataControllerInterface;
use Embark\CMS\Metadata\MetadataControllerTrait;

class Controller implements MetadataControllerInterface
{
    use MetadataControllerTrait;

    protected static $directory = '/data/fields';
    protected static $extension = '.xml';
}
