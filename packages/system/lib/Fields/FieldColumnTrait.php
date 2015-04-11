<?php

namespace Embark\CMS\Fields;

use Embark\CMS\Actors\Schemas\DatasourceQuery;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Structures\MetadataTrait;
use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataReferenceInterface;
use Embark\CMS\Structures\Boolean;
use Embark\CMS\Structures\Enum;
use DOMElement;
use Widget;

trait FieldColumnTrait
{
	use MetadataTrait;

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

    public function appendHeaderElement(DOMElement $wrapper, $active, $url)
    {
        $document = $wrapper->ownerDocument;
        $header = $document->createElement('dt');
        $header->addClass('col');
        $wrapper->appendChild($header);
        $wrapper->addClass($this['size']);

        // Add sorting information:
        if ($this['sorting'] instanceof MetadataInterface) {
            $direction = (
                'asc' === $this['sorting']['direction']
                    ? 'desc'
                    : 'asc'
            );

            $link = Widget::Anchor(
                $this['name'],
                $url . '?sort=' . $this['name'] . '&direction=' . $direction
            );
            $header->appendChild($link);

            if ($active) {
                $link->addClass('active');
            }
        }

        else {
            $header->setValue($this['name']);
        }
    }
}