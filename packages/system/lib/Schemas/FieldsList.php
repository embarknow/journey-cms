<?php

namespace Embark\CMS\Schemas;

use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;

class FieldsList implements MetadataInterface
{
    use MetadataTrait {
        MetadataTrait::findAll as findAllRaw;
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
        foreach ($this->findAllRaw() as $name => $value) {
            $type = $value->createType();
            $type['data'] = $value;

            // var_dump($value, $type);

            yield $name => $type;
        }

        // exit;
    }
}