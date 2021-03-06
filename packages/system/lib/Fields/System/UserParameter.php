<?php

namespace Embark\CMS\Fields\System;

use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldParameterInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Metadata\MetadataTrait;

class UserParameter implements FieldParameterInterface
{
    use MetadataTrait;

    public function appendParameter(array &$parameters, DatasourceInterface $datasource, SchemaInterface $section, EntryInterface $entry)
    {
        $key = sprintf('ds-%s.system.%s', $datasource['handle'], 'user');

        if (false === isset($parameters[$key])) {
            $parameters[$key] = [];
        }

        $parameters[$key][] = $entry->user;
    }
}
