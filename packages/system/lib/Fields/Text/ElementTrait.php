<?php

namespace Embark\CMS\Fields\Text;

use DOMElement;
use Exception;
use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Metadata\Filters\Boolean;

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

    public function appendElement(DOMElement $wrapper, DatasourceInterface $datasource, SchemaInterface $schema, EntryInterface $entry, FieldInterface $field = null)
    {
        if (false === isset($field)) {
            $field = $this['field']->resolveInstanceOf(FieldInterface::class);
        }

        $document = $wrapper->ownerDocument;
        $data = $field['data']->read($schema, $entry, $field);

        if (isset($data->value) || isset($data->value_formatted)) {
            $element = $document->createElement($field['schema']['handle']);
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
