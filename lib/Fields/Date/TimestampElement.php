<?php

namespace Embark\CMS\Fields\Date;

use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use Embark\CMS\SystemDateTime;
use DOMDocument;
use Entry;
use Field;
use General;

class TimestampElement implements MetadataInterface
{
	use MetadataTrait;

	public function createElement(DOMDocument $document, Field $field, Entry $entry)
	{
		$element = $document->createElement($this['field']);
		$data = $entry->data()->{$this['field']};

		if (isset($data->value) && !is_null($data->value)) {
			$date = new SystemDateTime($data->value);
			$date = $date->toUserDateTime();
			$element->setAttribute('timezone', $date->getTimeZone()->getName());
			$element->setAttribute('unix-timestamp', $date->getTimestamp());
		}

		return $element;
	}
}