<?php

namespace Embark\CMS\Fields\System;

use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Database\TableAliasIndex;
use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use Embark\CMS\Structures\SortingDirection;

class RandomSortQuery implements MetadataInterface
{
    use MetadataTrait;

    public function buildQuery(SchemaInterface $schema, FieldInterface $field, TableAliasIndex $tables, array &$joins, array &$sorts)
    {
        $sorts[] = 'rand()';
    }
}
