<?php

namespace Embark\CMS\Relationships;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;

use Embark\CMS\Relationships\RelationshipType;

class RelationshipsList implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'relationship-type' => [
                'list' => true,
                'type' => new RelationshipType
            ]
        ]);
    }
}
