<?php

namespace Embark\CMS\Fields\Date;

use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use Embark\CMS\SystemDateTime;
use DOMDocument;
use Entry;
use Field;
use General;

class Element implements MetadataInterface
{
	use MetadataTrait;

	public function createElement(DOMDocument $document, Field $field, Entry $entry)
	{
		$element = $document->createElement($this['field']);
		$data = $entry->data()->{$this['field']};

		if (isset($data->value) && !is_null($data->value)) {
			$date = new SystemDateTime($data->value);
			$date = $date->toUserDateTime();

			return General::createXMLDateObject(
				$document, $date, $this['field']
			);
		}

		return $element;
	}
}