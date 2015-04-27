<?php

namespace Embark\CMS\Relationships;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Metadata\Filters\Integer;

use Embark\CMS\Schemas\FieldsList;
use Embark\CMS\Relationships\ObjectsList;

class RelationshipType implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'fields' => [
                'required' => true,
                'type' => new FieldsList
            ],
            'left-side' => [
                'handle' => [
                    'required' => true,
                    'default' => ''
                ],
                'max-entities' => [
                    'required' => false,
                    'filter' => new Integer,
                ],
                'min-entities' => [
                    'required' => false,
                    'filter' => new Integer,
                ],
                'objects' => [
                    'required' => true,
                    'type' => new ObjectsList
                ]
            ],
            'right-side' => [
                'handle' => [
                    'required' => true,
                    'default' => ''
                ],
                'max-entities' => [
                    'required' => false,
                    'filter' => new Integer
                ],
                'min-entities' => [
                    'required' => false,
                    'filter' => new Integer
                ],
                'objects' => [
                    'required' => true,
                    'type' => new ObjectsList
                ]
            ]
        ]);
    }
}
