<?php

namespace Embark\CMS\Actors\Schemas;

use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldElementInterface;
use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use Embark\CMS\Schemas\Schema;
use DOMElement;
use Entry;
use Field;
use Section;

class DatasourceOutputElements implements MetadataInterface {
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'element' => [
                'list' =>   true
            ]
        ]);
    }

    public function appendElements(DOMElement $wrapper, DatasourceInterface $datasource, Schema $schema, Entry $entry)
    {
        $document = $wrapper->ownerDocument;

        foreach ($this->findInstancesOf(FieldElementInterface::class) as $item) {
            $item->appendElement($wrapper, $datasource, $schema, $entry);
        }
    }

    // public function containsInstanceOf($class) {
    //     foreach ($this->findAll() as $value) {
    //         $reflect = new \ReflectionObject($value);

    //         if ($class !== $reflect->getName()) continue;

    //         return true;
    //     }

    //     return false;
    // }

    // public function containsInstanceOfField($class, $field) {
    //     foreach ($this->findAll() as $value) {
    //         $reflect = new \ReflectionObject($value);

    //         if ($class !== $reflect->getName()) continue;
    //         if ($value['field'] !== $field) continue;

    //         return true;
    //     }

    //     return false;
    // }
}