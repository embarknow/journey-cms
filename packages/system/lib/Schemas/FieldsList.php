<?php

namespace Embark\CMS\Schemas;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;

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
