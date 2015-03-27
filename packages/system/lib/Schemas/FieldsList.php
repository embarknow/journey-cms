<?php

namespace Embark\CMS\Schemas;

use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;

class FieldsList implements MetadataInterface
{
    use MetadataTrait {
        MetadataTrait::findAll as findAllItems;
    }

    public function __construct()
    {
        $this->setSchema([
            'item' => [
                'type' =>       new FieldsListItem()
            ]
        ]);
    }

    public function findAll()
    {
        foreach ($this->findAllItems() as $name => $value) {
            $type = $value->createType();
            $type['data'] = $value;

            yield $name => $type;
        }
    }

    public function findAllWithGuids()
    {
        foreach ($this->findAllItems() as $name => $value) {
            // $type = $value->createType();
            // $type['data'] = $value;

            yield $value['guid'] => $value;
        }
    }
}