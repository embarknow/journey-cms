<?php

namespace Embark\CMS\Fields\System;

use Embark\CMS\Actors\Schemas\DatasourceQuery;
use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use Embark\CMS\Structures\SortingDirection;

class IdSortQuery implements MetadataInterface
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

    public function appendQuery(DatasourceQuery $query, SchemaInterface $schema, FieldInterface $field)
    {
        $query->sortByMetadata('entry_id', $this['direction']);
    }
}
