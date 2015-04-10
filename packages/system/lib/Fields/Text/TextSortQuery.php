<?php

namespace Embark\CMS\Fields\Text;

use Embark\CMS\Actors\Schemas\DatasourceQuery;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldSortQueryInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Structures\MetadataTrait;
use Embark\CMS\Structures\SortingDirection;
use Symphony;

class TextSortQuery implements FieldSortQueryInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'direction' => [
                'filter' =>     new SortingDirection()
            ]
        ]);
    }

    public function appendQuery(DatasourceQuery $query, SchemaInterface $schema, FieldInterface $field = null)
    {
        if (false === isset($field)) {
            $field = $this['field']->resolveInstanceOf(FieldInterface::class);
        }

        $table = Symphony::Database()->createDataTableName(
            $schema['resource']['handle'],
            $field['schema']['handle'],
            $field->getGuid()
        );
        $query->sortBySubQuery("select entry_id, value as order_id from {$table}", $this['direction']);
    }
}
