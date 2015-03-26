<?php

namespace Embark\CMS\Fields;

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

class CreationDateElement implements MetadataInterface
{
	use MetadataTrait;

	public function appendElement(DOMElement $wrapper, DatasourceInterface $datasource, Schema $section, Entry $entry)
	{
		$document = $wrapper->ownerDocument;
		$date = new SystemDateTime($entry->creation_date);
		$date = $date->toUserDateTime();

		$wrapper->appendChild(General::createXMLDateObject(
			$document, $date, 'creation-date'
		));
	}
}