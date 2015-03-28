<?php

namespace Embark\CMS\Views\Section;

use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use DOMElement;

class SectionTableView implements MetadataInterface
{
    use MetadataTrait;

    public function __construct(SectionView $view)
    {
        $this->setSchema([
            'item' => [
                'type' =>   new SectionTableColumn($view)
            ]
        ]);
    }

    public function appendView(DOMElement $wrapper)
    {
        $document = $wrapper->ownerDocument;
        $table = $document->createElement('table');
        $wrapper->appendChild($table);

        $head = $document->createElement('thead');
        $table->appendChild($head);

        foreach ($this->findAll() as $column) {
            if (isset($column['field']['column'])) {
                // var_dump($column); exit;
                $column->appendHeader($head);
            }
        }
    }
}