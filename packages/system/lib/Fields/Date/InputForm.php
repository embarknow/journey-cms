<?php

namespace Embark\CMS\Fields\Date;

use DOMElement;
use Exception;
use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldFormInterface;
use Embark\CMS\Fields\FieldRequiredException;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Metadata\Filters\Boolean;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\UserDateTime;
use Embark\CMS\SystemDateTime;
use DateTime;
use HTMLDocument;
use Widget;

class InputForm implements FieldFormInterface
{
    use MetadataTrait;

    protected $label;
    protected $input;

    public function __construct()
    {
        $this->setSchema([
            'required' => [
                'filter' =>     new Boolean()
            ],
            'autofill' => [
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
        $this->input->setAttribute('type', 'text');
        $this->label->appendChild($this->input);

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
        if (isset($data->value) && $data->value instanceof SystemDateTime) {
            $date = $data->value->toUserDateTime();
            $this->input->setAttribute('value', $date->format('Y-m-d H:i:s'));
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
