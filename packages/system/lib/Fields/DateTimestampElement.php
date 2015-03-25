<?php

namespace Embark\CMS\Fields;

use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use Embark\CMS\SystemDateTime;
use DOMElement;
use Entry;
use Field;
use General;
use Section;

class DateTimestampElement implements MetadataInterface
{
	use MetadataTrait;

	public function appendElement(DOMElement $wrapper, DatasourceInterface $datasource, Section $section, Entry $entry)
	{
		$field = $section->fetchFieldByHandle($this['field']);

		if (!($field instanceof Field)) return;

		$document = $wrapper->ownerDocument;
		$data = $entry->data()->{$this['field']};

		if (isset($data->value) && !is_null($data->value)) {
			$date = new SystemDateTime($data->value);
			$date = $date->toUserDateTime();

			$element = $document->createElement($this['field']);
			$element->setAttribute('timezone', $date->getTimeZone()->getName());
			$element->setAttribute('unix-timestamp', $date->getTimestamp());
			$wrapper->appendChild($element);
		}
	}
}