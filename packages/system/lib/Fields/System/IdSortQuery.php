<?php

namespace Embark\CMS\Fields\System;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldQueryInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Schemas\SchemaSelectQuery;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Metadata\Filters\SortingDirection;

class IdSortQuery implements FieldQueryInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'direction' => [
                'filter' =>        new SortingDirection()
            ]
        ]);
    }

    public function appendQuery(SchemaSelectQuery $query, SchemaInterface $schema, FieldInterface $field = null)
    {
        $query->sortByMetadata('entry_id', $this['direction']);
    }
}
