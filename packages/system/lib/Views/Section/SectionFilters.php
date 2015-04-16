<?php

namespace Embark\CMS\Views\Section;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldQueryInterface;
use Embark\CMS\Link;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Metadata\Types\MenuItem;
use Embark\CMS\Schemas\Controller as SchemaController;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Schemas\SchemaSelectQuery;
use HTMLDocument;

class SectionFilters implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'filter' => [
                'list' =>   true
            ]
        ]);
    }

    public function appendFilteringQueries(SchemaSelectQuery $query, SchemaInterface $schema)
    {
    	foreach ($this->findInstancesOf(FieldQueryInterface::class) as $filter) {
    		$filter->appendQuery($query, $schema);
    	}
    }
}
