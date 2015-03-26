<?php

namespace Embark\CMS\Fields;

use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use Embark\CMS\Schemas\Schema;
use Embark\CMS\SystemDateTime;
use DateTime;
use Entry;
use Section;

class ModificationDateParameter implements MetadataInterface
{
    use MetadataTrait;

    public function appendParameter(array &$parameters, DatasourceInterface $datasource, Schema $section, Entry $entry)
    {
        $key = sprintf('ds-%s.system.%s', $datasource['handle'], 'modification-date');

        if (false === isset($parameters[$key])) {
            $parameters[$key] = [];
        }

        $date = new SystemDateTime($entry->modification_date);
        $date = $date->toUserDateTime();
        $parameters[$key][] = $date->format(DateTime::W3C);
    }
}
