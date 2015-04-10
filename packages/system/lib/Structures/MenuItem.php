<?php

namespace Embark\CMS\Structures;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;

class MenuItem implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'order' => [
                'filter' => new Integer()
            ]
        ]);
    }
}
