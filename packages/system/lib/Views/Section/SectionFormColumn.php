<?php

namespace Embark\CMS\Views\Section;

use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use DOMElement;

class SectionFormColumn implements MetadataInterface
{
    use MetadataTrait;

    public function __construct(SectionView $view)
    {
        $this->setSchema([
            'fieldset' => [
                'list' =>   true,
                'type' =>   new SectionFormFieldset($view)
            ]
        ]);
    }

    public function findAllForms()
    {
        foreach ($this->findInstancesOf(SectionFormFieldset::class) as $item) {
            foreach ($item->findAllForms() as $field) {
                yield $field;
            }
        }
    }

    public function appendColumn(DOMElement $wrapper)
    {
        $document = $wrapper->ownerDocument;
        $column = $document->createElement('div');
        $column->addClass('column ' . $this['size']);
        $wrapper->appendChild($column);

        foreach ($this->findInstancesOf(SectionFormFieldset::class) as $item) {
            $item->appendFieldset($column);
        }
    }
}