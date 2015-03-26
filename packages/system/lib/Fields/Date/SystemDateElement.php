<?php

namespace Embark\CMS\Fields\Date;

use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use Embark\CMS\Schemas\Schema;
use Embark\CMS\SystemDateTime;
use DOMElement;
use Entry;
use Field;
use General;
use Section;

class SystemDateElement implements MetadataInterface
{
	use MetadataTrait;

	public function appendElement(DOMElement $wrapper, DatasourceInterface $datasource, Schema $section, Entry $entry)
	{
		$field = $section->findField($this['field']);

		if (false === $field) return;

		$document = $wrapper->ownerDocument;
		$data = $entry->data()->{$this['field']};

		if (isset($data->value) && !is_null($data->value)) {
			$date = new SystemDateTime($data->value);

			$wrapper->appendChild(General::createXMLDateObject(
				$document, $date, $this['field']
			));
		}
	}
}