<?php

namespace Embark\CMS\Fields\Boolean;

use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldQueryInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Schemas\SchemaSelectQuery;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Metadata\Filters\SortingDirection;
use Symphony;

class SortQuery implements FieldQueryInterface
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

    public function appendQuery(SchemaSelectQuery $query, SchemaInterface $schema, FieldInterface $field = null)
    {
        if (false === isset($field)) {
            $field = $this['field']->resolveInstanceOf(FieldInterface::class);
        }

        $table = Symphony::Database()->createDataTableName(
            $schema['resource']['handle'],
            $field['handle'],
            $field->getGuid()
        );
        $query->sortBySubQuery("select entry_id, value as order_id from {$table}", $this['direction']);
    }
}
