<?php

namespace Embark\CMS\Fields\Text;

use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use DOMDocument;
use Entry;
use Exception;
use Field;

trait ElementTrait
{
	public function createElement(DOMDocument $document, Field $field, Entry $entry)
	{
		$element = $document->createElement($this['field']);
		$data = $entry->data()->{$this['field']};

		try {
			$this->appendValue($document, $element, $field, $data);
		}

		catch (Exception $e) {
			// Only get 'Document Fragment is empty' errors here.
		}

		if (isset($data->handle) && $field->{'text-handle'} == 'yes') {
			$element->setAttribute('handle', $data->handle);
		}

		return $element;
	}
}