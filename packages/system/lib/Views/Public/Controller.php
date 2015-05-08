<?php

namespace Embark\CMS\Views\Public;

use Embark\CMS\Metadata\MetadataControllerInterface;
use Embark\CMS\Metadata\MetadataControllerTrait;

class Controller implements MetadataControllerInterface
{
    use MetadataControllerTrait;

    protected static $directory = '/blueprints/views/public';
    protected static $extension = '.xml';
}
