<?php

namespace Embark\CMS\Actors\Schemas;

use ReflectionObject;
use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
// use Embark\CMS\Fields\Parameter;
use Embark\CMS\Schemas\Schema;
use Entry;
use Field;
use Section;

class DatasourceOutputParameters implements MetadataInterface {
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'item' => [
                // 'type' =>       new Parameter()
            ]
        ]);
    }

    public function appendSchema(array &$schema, Schema $section)
    {
        foreach ($this->findAll() as $item) {
            if (isset($schema[$item['field']])) continue;

            $field = $section->findField($item['field']);

            if (false === $field) continue;

            $schema[$item['field']] = $field;
        }
    }

    public function appendParameters(array &$parameters, DatasourceInterface $datasource, Schema $section, Entry $entry)
    {
        foreach ($this->findAll() as $item) {
            $item->appendParameter($parameters, $datasource, $section, $entry);
        }
    }

    public function containsInstanceOf($class) {
        foreach ($this->findAll() as $value) {
            $reflect = new ReflectionObject($value);

            if ($class !== $reflect->getName()) continue;

            return true;
        }

        return false;
    }

    public function containsField($field) {
        foreach ($this->findAll() as $value) {
            if ($value['field'] !== $field) continue;

            return true;
        }

        return false;
    }
}
