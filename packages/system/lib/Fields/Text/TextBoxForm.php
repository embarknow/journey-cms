<?php

namespace Embark\CMS\Fields\Text;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldFormInterface;
use Embark\CMS\Fields\FieldRequiredException;
use Embark\CMS\Structures\Enum;
use Embark\CMS\Structures\MetadataTrait;
use DOMElement;
use Exception;
use HTMLDocument;
use SymphonyDOMElement;
use Widget;

class TextBoxForm extends TextInputForm
{
    protected $label;
    protected $input;

    public function __construct()
    {
        $this->setSchema([
            'size' => [
                'filter' =>     new Enum(['small', 'medium', 'large', 'huge'])
            ]
        ]);
    }

    protected function appendInput(DOMElement $wrapper, $handle)
    {
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