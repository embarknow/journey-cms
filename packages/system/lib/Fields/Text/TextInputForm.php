<?php

namespace Embark\CMS\Fields\Text;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldFormInterface;
use Embark\CMS\Fields\FieldRequiredException;
use Embark\CMS\Structures\Boolean;
use Embark\CMS\Structures\MetadataReferenceInterface;
use Embark\CMS\Structures\MetadataTrait;
use DOMElement;
use Exception;
use HTMLDocument;
use SymphonyDOMElement;
use Widget;

class TextInputForm implements FieldFormInterface
{
    use MetadataTrait;

    protected $label;
    protected $input;

    public function __construct()
    {
        $this->setSchema([
            'data' => [
                'type' =>   new TextData()
            ]
        ]);
    }

    public function appendPublishHeaders(HTMLDocument $page, EntryInterface $entry, FieldInterface $field, array &$headersAppended)
    {
        // Make sure we only append our headers once:
        if (!in_array(get_class($field), $headersAppended)) {
            $page->insertNodeIntoHead($page->createStylesheetElement(URL . '/extensions/field_textbox/assets/publish.css'));
            $page->insertNodeIntoHead($page->createScriptElement(URL . '/extensions/field_textbox/assets/publish.js'));

            $headersAppended[] = get_class($field);
        }
    }

    protected function appendInput(DOMElement $wrapper, $handle)
    {
        $this->input = Widget::Input("fields[{$handle}]");
        $wrapper->appendChild($this->input);
    }

    public function appendPublishForm(DOMElement $wrapper)
    {
        $data = $this['data']->resolveInstanceOf(TextData::class);
        $field = $this['field']->resolveInstanceOf(TextField::class);

        $handle = $field['schema']['handle'];
        $document = $wrapper->ownerDocument;

        $div = $document->createElement('div');
        $div->addClass('field field-textbox');
        $wrapper->appendChild($div);

        $this->label = $document->createElement('label');
        $this->label->setValue($this['name']);
        $div->appendChild($this->label);

        $this->appendInput($this->label, $handle);

        // Show maximum text length label:
        if ($data['max-length'] > 0) {
            $optional = $document->createElement('em', __('$1 of $2 remaining'));
            $optional->addClass('maxlength');
            $this->label->prependChild($optional);
            $this->input->setAttribute('maxlength', $data['max-length']);
        }

        // Mark field as optional:
        if (
            false === isset($data['required'])
            || false === $data['required']
        ) {
            $this->label->prependChild($document->createElement('em', __('Optional')));
        }
    }

    public function setData(EntryInterface $entry, FieldInterface $field, $data)
    {
        if (isset($data->value)) {
            $this->input->setAttribute('value', $data->value);
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

        // Field was not filled in:
        else if ($error instanceof TextLengthException) {
            Widget::Error($this->label, __(
                '%s is limited to %d characters.',
                [$this['name'], $field['data']['max-length']]
            ));
        }

        // An unknown error:
        else {
            Widget::Error($this->label, $error->getMessage());
        }
    }
}