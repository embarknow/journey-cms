<?php

namespace Embark\CMS\Configuration;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;

class Region implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([

        ]);
    }
}
