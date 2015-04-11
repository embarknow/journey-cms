<?php

namespace Embark\CMS\Metadata\Types;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Metadata\Filters\Integer;

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
