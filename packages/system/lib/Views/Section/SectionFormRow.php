<?php

namespace Embark\CMS\Views\Section;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
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
        foreach ($this->findInstancesOf(SectionFormColumn::class) as $item) {
            foreach ($item->findAllFields() as $field) {
                yield $field;
            }
        }
    }

    public function appendRow(DOMElement $wrapper)
    {
        $document = $wrapper->ownerDocument;
        $row = $document->createElement('div');
        $row->addClass('columns');
        $wrapper->appendChild($row);

        foreach ($this->findInstancesOf(SectionFormColumn::class) as $item) {
            $item->appendColumn($row);
        }
    }
}
