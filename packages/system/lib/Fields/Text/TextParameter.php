<?php

namespace Embark\CMS\Fields\Text;

use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldParameterInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Structures\MetadataTrait;

class TextParameter implements FieldParameterInterface
{
    use MetadataTrait;

    public function appendParameter(array &$parameters, DatasourceInterface $datasource, SchemaInterface $section, EntryInterface $entry)
    {
        $field = $section->findField($this['field']);
        $key = sprintf('ds-%s.%s', $datasource['handle'], $this['field']);

        if (false === $field) return;

        $data = $field->getParameterOutputValue($entry->data()->{$this['field']}, $entry);

        if (false === isset($parameters[$key])) {
            $parameters[$key] = [];
        }

        if (is_array($data)) {
            $parameters[$key] = array_merge($data, $parameters[$key]);
        }

        else {
            $parameters[$key][] = $data;
        }
    }
}