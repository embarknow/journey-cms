<?php

namespace Embark\CMS\Fields\Text;

use Embark\CMS\Database\TableAliasIndex;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use Embark\CMS\Structures\SortingDirection;
use Symphony;

class TextSortQuery implements MetadataInterface
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

    public function buildQuery(SchemaInterface $schema, FieldInterface $field, TableAliasIndex $tables, array &$joins, array &$sorts)
    {
        $table = Symphony::Database()->createDataTableName(
            $schema['resource']['handle'],
            $field['schema']['handle'],
            $field['schema']['guid']
        );

        if (false === isset($tables[$table])) {
            $tables[$table] = $tables->next();
            $joins[] = "right join\n\t`{$table}` as `{$tables[$table]}`\n\ton ({$tables['entries']}.id = {$tables[$table]}.entry_id)";
        }

        $sorts[] = $tables[$table] . '.value ' . $this['direction'];
    }
}
