<?php

namespace Embark\CMS\Fields;

use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use Embark\CMS\SystemDateTime;
use DateTime;
use Entry;
use Section;

class CreationDateParameter implements MetadataInterface {
	use MetadataTrait;

	public function appendParameter(array &$parameters, DatasourceInterface $datasource, Section $section, Entry $entry)
	{
		$key = sprintf('ds-%s.system.%s', $datasource['handle'], 'creation-date');

		if (false === isset($parameters[$key])) {
			$parameters[$key] = [];
		}

		$date = new SystemDateTime($entry->creation_date);
		$date = $date->toUserDateTime();
		$parameters[$key][] = $date->format(DateTime::W3C);
	}
}