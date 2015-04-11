<?php

namespace Embark\CMS\Relationships;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Metadata\Filters\Guid;
use Symphony;

class List implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'max-items' => [
                'required' => false,
                'filter'   => new Integer(),
                'default'  => 0
            ],
            'min-items' => [
                'required' => false,
                'filter'   => new Integer(),
                'default'  => 0
            ],
            'item' => [
                'type' =>     new ListItem()
            ]
        ]);
    }

}
