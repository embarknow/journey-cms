<?php

namespace Embark\CMS\Actors\Schemas;

use Embark\CMS\Fields\FieldSortQueryInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Structures\Boolean;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use DOMDocument;

class DatasourceSortingQuery implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'query' => [
                'list' =>   true
            ]
        ]);
    }

    public function appendQuery(DatasourceQuery $query, SchemaInterface $schema)
    {
        foreach ($this->findInstancesOf(FieldSortQueryInterface::class) as $item) {
            $item->appendQuery($query, $schema);
        }
    }

    public function createElement(DOMDocument $document)
    {
        $element = $document->createElement('sorting');

        $element->setAttribute('field', $this['field']);
        $element->setAttribute('direction', $this['direction']);

        return $element;
    }
}
