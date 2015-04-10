<?php

namespace Embark\CMS\Fields\System;

use Embark\CMS\Actors\Schemas\DatasourceQuery;
use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldSortQueryInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Structures\MetadataTrait;
use Embark\CMS\Structures\SortingDirection;

class RandomSortQuery implements FieldSortQueryInterface
{
    use MetadataTrait;

    public function appendQuery(DatasourceQuery $query, SchemaInterface $schema, FieldInterface $field = null)
    {
        $query->sortRandomly();
    }
}
