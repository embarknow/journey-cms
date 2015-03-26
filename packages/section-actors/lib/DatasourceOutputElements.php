<?php

namespace Embark\CMS\Actors\Section;

use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use Embark\CMS\Schemas\Schema;
use DOMElement;
use Entry;
use Field;
use Section;

class DatasourceOutputElements implements MetadataInterface {
	use MetadataTrait;

	public function appendSchema(array &$schema, Schema $section)
	{
		foreach ($this->findAll() as $item) {
			if (isset($schema[$item['field']])) continue;

			$field = $section->findField($item['field']);

			if (false === $field) continue;

			$schema[$item['field']] = $field;
		}
	}

	public function appendElements(DOMElement $wrapper, DatasourceInterface $datasource, Schema $section, Entry $entry)
	{
		$document = $wrapper->ownerDocument;

		foreach ($this->findAll() as $item) {
			$item->appendElement($wrapper, $datasource, $section, $entry);
		}
	}

	public function containsInstanceOf($class) {
		foreach ($this->findAll() as $value) {
			$reflect = new \ReflectionObject($value);

			if ($class !== $reflect->getName()) continue;

			return true;
		}

		return false;
	}

	public function containsInstanceOfField($class, $field) {
		foreach ($this->findAll() as $value) {
			$reflect = new \ReflectionObject($value);

			if ($class !== $reflect->getName()) continue;
			if ($value['field'] !== $field) continue;

			return true;
		}

		return false;
	}
}