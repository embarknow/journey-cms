<?php

namespace Embark\CMS\Fields\System;

use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldParameterInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Structures\MetadataTrait;

class IdParameter implements FieldParameterInterface
{
    use MetadataTrait;

    public function appendParameter(array &$parameters, DatasourceInterface $datasource, SchemaInterface $section, EntryInterface $entry)
    {
        $key = sprintf('ds-%s.system.%s', $datasource['handle'], 'id');

        if (false === isset($parameters[$key])) {
            $parameters[$key] = [];
        }

        $parameters[$key][] = $entry->entry_id;
    }
}
