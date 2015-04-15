<?php

namespace Embark\CMS\Fields\System;

use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldParameterInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\SystemDateTime;
use DateTime;

class CreationDateParameter implements FieldParameterInterface
{
    use MetadataTrait;

    public function appendParameter(array &$parameters, DatasourceInterface $datasource, SchemaInterface $section, EntryInterface $entry)
    {
        $key = sprintf('ds-%s.system.%s', $datasource['handle'], 'creation-date');

        if (false === isset($parameters[$key])) {
            $parameters[$key] = [];
        }

        $date = new SystemDateTime($entry->creation_date);
        $date = $date->toUserDateTime();
        $parameters[$key][] = $date->format(DateTime::W3C);
    }
}
