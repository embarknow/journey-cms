<?php

namespace Embark\CMS\Relationships;

use Embark\CMS\Relationships\SourcesList;
use Embark\CMS\Relationships\TargetsList;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Structures\Guid;

class Relationship implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'guid' => [
                'required' =>   true,
                'filter' =>     new Guid(),
                'default' =>    uniqid()
            ],
            'name' => [
                'required' =>   true,
                'default' =>    ''
            ],
            'handle' => [
                'required' =>   true,
                'default' =>    ''
            ],
            'sources' => [
                'required' =>   true,
                'type' =>       new SourcesList()
            ],
            'targets' => [
                'required' =>   false,
                'type' =>       new TargetsList()
            ]
        ]);
    }
}
