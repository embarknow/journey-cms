<?php

namespace Embark\CMS\Schemas;

use Embark\CMS\Structures\MetadataControllerTrait;

class Controller
{
    use MetadataControllerTrait;

    const DIR = WORKSPACE . '/schemas';
    const FILE_EXTENSION = '.xml';
}