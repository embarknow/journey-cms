<?php

namespace Embark\CMS\Fields;

use Embark\CMS\Actors\Schemas\DatasourceQuery;
use Embark\CMS\Link;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataReferenceInterface;
use Embark\CMS\Metadata\Filters\Boolean;
use Embark\CMS\Metadata\Filters\Enum;
use Embark\CMS\Schemas\SchemaInterface;
use DOMElement;
use Widget;

trait FieldColumnTrait
{
    use MetadataTrait;

    protected $sortingActive = false;

    public function __construct()
    {
        $this->setSchema([
            'editLink' => [
                'filter' =>     new Boolean()
            ],
            'size' => [
                'filter' =>     new Enum(['small', 'medium', 'large', 'huge']),
                'default' =>    'medium',
                'required' =>   true
            ]
        ]);
    }

    public function appendSortingQuery(DatasourceQuery $query, SchemaInterface $schema, $direction = null)
    {
        if ($this['sorting'] instanceof MetadataInterface) {
            $this->sortingActive = true;

            if (isset($direction)) {
                $this['sorting']['direction'] = $direction;
            }

            if ($this['field'] instanceof FieldInterface) {
                $this['sorting']->appendQuery($query, $schema, $this['field']);
            }

            else if ($this['field'] instanceof MetadataReferenceInterface) {
                $this['sorting']->appendQuery($query, $schema, $this['field']->resolve());
            }
        }
    }

    public function appendHeaderElement(DOMElement $wrapper, Link $link)
    {
        $document = $wrapper->ownerDocument;
        $header = $document->createElement('dt');
        $wrapper->appendChild($header);
        $wrapper->addClass($this['size']);

        // Add sorting information:
        if ($this['sorting'] instanceof MetadataInterface) {
            $wrapper->addClass('sortable');
            $anchor = $document->createElement('a');
            $header->appendChild($anchor);

            // Change sorting direction:
            if ($this->sortingActive) {
                $wrapper->addClass('active');
                $link = $link->withParameter('direction', (
                    'asc' === $this['sorting']['direction']
                        ? 'desc'
                        : 'asc'
                ));
            }

            else {
                $link = $link->withParameter('sort', $this['name']);
            }

            $anchor->setAttribute('href', (string)$link);
            $anchor->setValue($this['name']);
        }

        else {
            $header->setValue($this['name']);
        }
    }
}
