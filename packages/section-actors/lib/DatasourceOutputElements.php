<?php

namespace Embark\CMS\Actors\Section;

use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use DOMElement;
use Entry;
use Field;
use Section;

class DatasourceOutputElements implements MetadataInterface
{
    use MetadataTrait;

    public function appendSchema(array &$schema, Section $section)
    {
        foreach ($this->getIterator() as $item) {
            if (isset($schema[$item['field']])) {
                continue;
            }

            $field = $section->fetchFieldByHandle($item['field']);

            if (!$field instanceof Field) {
                continue;
            }

            $schema[$item['field']] = $field;
        }
    }

    public function appendElements(DOMElement $wrapper, DatasourceInterface $datasource, Section $section, Entry $entry)
    {
        $document = $wrapper->ownerDocument;

        foreach ($this->getIterator() as $item) {
            $item->appendElement($wrapper, $datasource, $section, $entry);
        }
    }

    public function containsInstanceOf($class)
    {
        foreach ($this->getIterator() as $value) {
            $reflect = new \ReflectionObject($value);

            if ($class !== $reflect->getName()) {
                continue;
            }

            return true;
        }

        return false;
    }

    public function containsInstanceOfField($class, $field)
    {
        foreach ($this->getIterator() as $value) {
            $reflect = new \ReflectionObject($value);

            if ($class !== $reflect->getName()) {
                continue;
            }

            if ($value['field'] !== $field) {
                continue;
            }

            return true;
        }

        return false;
    }
}
