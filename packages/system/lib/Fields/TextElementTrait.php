<?php

namespace Embark\CMS\Fields;

use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use DOMElement;
use Entry;
use Exception;
use Field;
use Section;

trait TextElementTrait
{
	public function appendElement(DOMElement $wrapper, DatasourceInterface $datasource, Section $section, Entry $entry)
	{
		$field = $section->fetchFieldByHandle($this['field']);

		if (!($field instanceof Field)) return;

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

			if ($field->{'text-handle'} == 'yes') {
				$element->setAttribute('handle', $data->handle);
			}
		}

		return $element;
	}
}