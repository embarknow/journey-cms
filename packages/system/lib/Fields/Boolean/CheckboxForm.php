<?php

namespace Embark\CMS\Fields\Boolean;

use DOMElement;
use Exception;
use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldFormInterface;
use Embark\CMS\Fields\FieldRequiredException;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Metadata\Filters\Boolean;
use Embark\CMS\Schemas\SchemaInterface;
use HTMLDocument;
use Widget;

class CheckboxForm implements FieldFormInterface
{
    use MetadataTrait;

    protected $label;
    protected $input;

    public function __construct()
    {
        $this->setSchema([
            'required' => [
                'filter' =>     new Boolean()
            ]
        ]);
    }

    public function appendPublishHeaders(HTMLDocument $page, EntryInterface $entry, FieldInterface $field, array &$headersAppended)
    {
    }

    public function appendPublishForm(DOMElement $wrapper, FieldInterface $field)
    {
        $handle = $field['handle'];
        $document = $wrapper->ownerDocument;

        $div = $document->createElement('div');
        $div->addClass('field field-boolean form-checkbox');
        $wrapper->appendChild($div);

        $this->label = $document->createElement('label');
        $this->label->setValue($this['name']);
        $div->appendChild($this->label);

        $this->input = Widget::Input("fields[{$handle}]");
        $this->input->setAttribute('type', 'checkbox');
        $this->input->setAttribute('value', 'yes');
        $this->label->prependChild($this->input);

        $default = Widget::Input("fields[{$handle}]");
        $default->setAttribute('type', 'hidden');
        $default->setAttribute('value', 'no');
        $this->label->prependChild($default);

        // Mark field as optional:
        if (
            false === isset($this['required'])
            || false === $this['required']
        ) {
            $this->label->prependChild($document->createElement('em', __('Optional')));
        }
    }

    public function setData(EntryInterface $entry, FieldInterface $field, $data)
    {
        if (isset($data->value) && $data->value) {
            $this->input->setAttribute('checked', 'checked');
        }
    }

    public function setError(EntryInterface $entry, FieldInterface $field, Exception $error)
    {
        // Field was not filled in:
        if ($error instanceof FieldRequiredException) {
            Widget::Error($this->label, __(
                '%s is a required field.',
                [$this['name']]
            ));
        }

        // An unknown error:
        else {
            Widget::Error($this->label, $error->getMessage());
        }
    }
}
