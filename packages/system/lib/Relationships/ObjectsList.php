<?php

namespace Embark\CMS\Relationships;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;

class ObjectsList implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'object' => [
                'list' => true,
                'type' => new ObjectsListItem
            ]
        ]);
    }
}
