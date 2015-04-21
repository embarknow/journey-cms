<?php

namespace Embark\CMS\Fields\Text;

use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldQueryInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Schemas\SchemaSelectQuery;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Metadata\Filters\Boolean;
use Symphony;

class FilterQuery implements FieldQueryInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'value' => [
                'required' =>   true
            ],
            'negate' => [
                'default' =>    false,
                'filter' =>     new Boolean(),
                'required' =>   true
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
        $operator = (
            $this['negate']
                ? '!='
                : '='
        );

        $query->filterBySubQuery("select entry_id from {$table} where value {$operator} :value", [
            ':value' =>     $this['value']
        ]);
    }
}
