<?php

namespace Embark\CMS\Fields;

use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use Embark\CMS\Schemas\Schema;
use Entry;
use Field;
use Section;

class Parameter implements MetadataInterface {
	use MetadataTrait;

	public function appendParameter(array &$parameters, DatasourceInterface $datasource, Schema $section, Entry $entry)
	{
		$field = $section->findField($this['field']);
		$key = sprintf('ds-%s.%s', $datasource['handle'], $this['field']);

		if (false === $field) return;

		$data = $field->getParameterOutputValue($entry->data()->{$this['field']}, $entry);

		if (false === isset($parameters[$key])) {
			$parameters[$key] = [];
		}

		if (is_array($data)) {
			$parameters[$key] = array_merge($data, $parameters[$key]);
		}

		else {
			$parameters[$key][] = $data;
		}
	}
}