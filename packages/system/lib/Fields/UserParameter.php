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

class UserParameter implements MetadataInterface
{
    use MetadataTrait;

    public function appendParameter(array &$parameters, DatasourceInterface $datasource, Schema $section, Entry $entry)
    {
        $key = sprintf('ds-%s.system.%s', $datasource['handle'], 'user');

        if (false === isset($parameters[$key])) {
            $parameters[$key] = [];
        }

        $parameters[$key][] = $entry->user;
    }
}
