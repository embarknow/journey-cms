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

class ModificationDateSortQuery implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
    	$this->setSchema([
    		'direction' => [
    			'filter' =>		new SortingDirection()
    		]
    	]);
    }

    public function buildQuery(SchemaInterface $schema, FieldInterface $field, TableAliasIndex $tables, array &$joins, array &$sorts)
    {
        $sorts[] = $tables['entries'] . '.modification_date ' . $this['direction'];
    }
}
