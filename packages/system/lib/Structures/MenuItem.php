<?php

namespace Embark\CMS\Structures;

use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;

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
