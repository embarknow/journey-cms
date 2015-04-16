<?php

namespace Embark\CMS\Fields\System;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldQueryInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Schemas\SchemaSelectQuery;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Metadata\Filters\SortingDirection;

class RandomSortQuery implements FieldQueryInterface
{
    use MetadataTrait;

    public function appendQuery(SchemaSelectQuery $query, SchemaInterface $schema, FieldInterface $field = null)
    {
        $query->sortRandomly();
    }
}
