<?php

namespace Embark\CMS\Schemas;

use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;

class FieldsList implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'item' => [
                'type' =>       new FieldsListItem()
            ]
        ]);
    }
}