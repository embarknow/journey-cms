<?php

namespace Embark\CMS\Schemas;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;

class ObjectssListItem implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'handle' => [
                'required' => true,
                'default' => ''
            ],
            'template' => [
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
        ]);
    }
}
