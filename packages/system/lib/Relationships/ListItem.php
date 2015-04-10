<?php

namespace Embark\CMS\Relationships;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Structures\Guid;
use Symphony;

class ListItem implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'schema' => [
                'required' => true
                'default' => ''
            ],
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
            'template' => [
                'required' => true
                'default' => ''
            ],
            'expression' => [
                'required' => true
                'default' => ''
            ]
        ]);
    }
}
