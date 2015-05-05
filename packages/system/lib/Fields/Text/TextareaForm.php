<?php

namespace Embark\CMS\Fields\Text;

use DOMElement;
use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Metadata\Filters\Boolean;
use Embark\CMS\Metadata\Filters\Enum;
use Embark\CMS\Metadata\Filters\Integer;
use Widget;

class TextareaForm extends InputForm
{
    protected $label;
    protected $input;

    public function __construct()
    {
        $this->setSchema([
            'required' => [
                'filter' =>     new Boolean()
            ],
            'max-length' => [
                'filter' =>     new Integer()
            ],
            'size' => [
                'filter' =>     new Enum(['small', 'medium', 'large', 'huge'])
            ]
        ]);
    }

    protected function appendForm(DOMElement $wrapper, FieldInterface $field)
    {
        $document = $wrapper->ownerDocument;
        $this->form = $document->createElement('div');
        $this->form->addClass('field field-text form-textarea');
        $wrapper->appendChild($this->form);
    }

    protected function appendInput(DOMElement $wrapper, FieldInterface $field)
    {
        $handle = $field['handle'];
        $this->input = Widget::Textarea("fields[{$handle}]");
        $wrapper->appendChild($this->input);

        if ($this['size']) {
            $this->input->addClass('size-' . $this['size']);
        }
    }

    public function setData(EntryInterface $entry, FieldInterface $field, $data)
    {
        if (isset($data->value)) {
            $this->input->setValue($data->value);
        }
    }
}
