<?php

namespace Embark\CMS\Fields\System;

use Embark\CMS\Actors\Schemas\DatasourceQuery;
use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldSortQueryInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Metadata\Filters\SortingDirection;

class CreationDateSortQuery implements FieldSortQueryInterface
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

    public function appendQuery(DatasourceQuery $query, SchemaInterface $schema, FieldInterface $field = null)
    {
        $query->sortByMetadata('creation_date', $this['direction']);
    }
}
