<?php

namespace Embark\CMS\Fields\Text;

use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Structures\Boolean;
use Embark\CMS\Structures\MetadataTrait;
use DOMElement;
use Exception;

trait ElementTrait
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'handle' => [
                'filter' =>     new Boolean()
            ]
        ]);
    }

    public function appendElement(DOMElement $wrapper, DatasourceInterface $datasource, SchemaInterface $schema, EntryInterface $entry)
    {
        $field = $schema->findField($this['field']);

        if (false === $field) return;

        $document = $wrapper->ownerDocument;
        $data = $entry->data()->{$this['field']};

        if (isset($data->value) || isset($data->value_formatted)) {
            $element = $document->createElement($this['field']);
            $wrapper->appendChild($element);

            try {
                $this->appendValue($element, $field, $data);
            }

            catch (Exception $e) {
                // Only get 'Document Fragment is empty' errors here.
            }

            if ($this['handle']) {
                $element->setAttribute('handle', $data->handle);
            }
        }

        return $element;
    }
}