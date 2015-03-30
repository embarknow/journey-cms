<?php

namespace Embark\CMS\Views\Section;

use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use DOMElement;

class SectionFormRow implements MetadataInterface
{
    use MetadataTrait;

    public function __construct(SectionView $view)
    {
        $this->setSchema([
            'column' => [
                'list' =>   true,
                'type' =>   new SectionFormColumn($view)
            ]
        ]);
    }

    public function findAllFields()
    {
        foreach ($this->findAll() as $item) {
            if ($item instanceof SectionFormColumn) {
                foreach ($item->findAllFields() as $field) {
                    yield $field;
                }
            }
        }
    }

    public function appendRow(DOMElement $wrapper)
    {
        $document = $wrapper->ownerDocument;
        $row = $document->createElement('div');
        $row->addClass('columns');
        $wrapper->appendChild($row);

        foreach ($this->findAll() as $item) {
            if ($item instanceof SectionFormColumn) {
                $item->appendColumn($row);
            }
        }
    }
}