<?php

namespace Embark\CMS\Actors;

use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use Entry;
use Field;
use Section;

class SectionDatasourceOutputParameters implements MetadataInterface {
	use MetadataTrait;

	public function appendSchema(array &$schema, Section $section)
	{
		foreach ($this->getIterator() as $item) {
			if (isset($schema[$item['field']])) continue;

			$field = $section->fetchFieldByHandle($item['field']);

			if (!$field instanceof Field) continue;

			$schema[$item['field']] = $field;
		}
	}

	public function appendParameters(array &$parameters, DatasourceInterface $datasource, Section $section, Entry $entry)
	{
		foreach ($this->getIterator() as $item) {
			$item->appendParameter($parameters, $datasource, $section, $entry);
		}
	}
}