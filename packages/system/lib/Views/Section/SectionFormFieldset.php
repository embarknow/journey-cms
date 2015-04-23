<?php

namespace Embark\CMS\Views\Section;

use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldDataInterface;
use Embark\CMS\Fields\FieldFormInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use DOMElement;

class SectionFormFieldset implements MetadataInterface
{
    use MetadataTrait;

    protected $view;

    public function __construct(SectionView $view)
    {
        $this->view = $view;
        $this->setSchema([
            'field' => [
                'list' =>   true
            ]
        ]);
    }

    public function findAllForms()
    {
        foreach ($this->findInstancesOf(FieldFormInterface::class) as $item) {
            yield $item;
        }
    }

    public function appendFieldset(DOMElement $wrapper)
    {
        $schema = $this->view['schema']->resolveInstanceOf(SchemaInterface::class);
        $document = $wrapper->ownerDocument;
        $fieldset = $document->createElement('fieldset');
        $wrapper->appendChild($fieldset);
        $legend = $document->createElement('h3');
        $legend->setValue($this['name']);
        $fieldset->appendChild($legend);

        foreach ($this->findInstancesOf(FieldFormInterface::class) as $item) {
            $field = $item['field']->resolveInstanceOf(FieldInterface::class);
            $item->appendPublishForm($fieldset, $field);
        }
    }
}
