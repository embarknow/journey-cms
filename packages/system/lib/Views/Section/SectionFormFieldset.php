<?php

namespace Embark\CMS\Views\Section;

use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Schemas\Controller;
use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
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

    public function findAllFields()
    {
        foreach ($this->findAll() as $item) {
            if ($item instanceof FieldInterface) {
                yield $item;
            }
        }
    }

    public function appendFieldset(DOMElement $wrapper)
    {
        $schema = Controller::read($this->view['schema']);
        $document = $wrapper->ownerDocument;
        $fieldset = $document->createElement('fieldset');
        $wrapper->appendChild($fieldset);
        $legend = $document->createElement('h3');
        $legend->setValue($this['name']);
        $fieldset->appendChild($legend);

        foreach ($this->findAll() as $item) {
            if ($item instanceof FieldInterface) {
                $field = $schema->findFieldByGuid($item['schema']['guid']);

                if ($field instanceof FieldInterface) {
                    $item->fromMetadata($field);
                    $item['form']->appendForm($fieldset, $item);
                }
            }
        }
    }
}