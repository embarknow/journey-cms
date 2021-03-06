<?php

namespace Embark\CMS\Actors\Schemas;

use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Metadata\Filters\Boolean;
use Embark\CMS\Metadata\Filters\Integer;
use Embark\CMS\Metadata\Filters\MaxInteger;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use DOMDocument;

class DatasourcePaginationQuery implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'append' => [
                'filter' => new Boolean()
            ],
            'limit' => [
                'filter' => new Integer()
            ],
            'page' => [
                'filter' => new Integer()
            ],
            'entries-per-page' => [
                'filter' => new MaxInteger(1)
            ],
            'current-page' => [
                'filter' => new MaxInteger(1)
            ],
            'total-entries' => [
                'filter' => new Integer()
            ],
            'total-pages' => [
                'filter' => new Integer()
            ]
        ]);
    }

    public function appendQuery(DatasourceQuery $query, SchemaInterface $schema)
    {
        $this['entries-per-page'] = $this['limit'];
        $this['current-page'] = $this['page'];

        $from = (
            (max(1, $this['current-page']) - 1) * $this['entries-per-page']
        );

        $query->limitTo($from, $this['entries-per-page']);
    }

    public function setTotal($total)
    {
        $this['total-entries'] = (integer) $total;
        $this['total-pages'] = (integer) ceil($this['total-entries'] * (1 / $this['entries-per-page']));
    }

    public function createElement(DOMDocument $document)
    {
        $element = $document->createElement('pagination');

        $element->setAttribute('total-entries', $this['total-entries']);
        $element->setAttribute('total-pages', $this['total-pages']);
        $element->setAttribute('entries-per-page', $this['entries-per-page']);
        $element->setAttribute('current-page', $this['current-page']);

        return $element;
    }
}
