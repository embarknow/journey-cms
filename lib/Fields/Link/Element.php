<?php

namespace Embark\CMS\Fields\Link;

use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use Embark\CMS\SystemDateTime;
use DOMDocument;
use DOMXPath;
use Entry;
use Field;
use General;
use Section;

class Element implements MetadataInterface
{
	use MetadataTrait;

	public function createElement(DOMDocument $document, Field $field, Entry $entry)
	{
		$element = $document->createElement($this['field']);
		$data = $entry->data()->{$this['field']};

		if (isset($data)) {
			if (false === is_array($data)) {
				$data = [$data];
			}

			$xpath = new DOMXPath($document);
			$groups = array();

			foreach ($data as $item) {
				if (!isset($item->relation_id) || is_null($item->relation_id)) continue;

				if (!isset($groups[$item->relation_id])) {
					$groups[$item->relation_id] = array();
				}

				$groups[$item->relation_id][] = $item;
			}

			foreach ($groups as $relations) {
				foreach ($relations as $relation) {
					list($section_handle, $field_handle) = $relation->relation_field;

					$item = $xpath->query('item[@id = ' . $relation->relation_id . ']', $list)->item(0);

					if (is_null($item)) {
						$section = Section::loadFromHandle($section_handle);

						$item = $document->createElement('item');
						$item->setAttribute('id', $relation->relation_id);
						$item->setAttribute('section-handle', $section_handle);
						$item->setAttribute('section-name', $section->name);

						$element->appendChild($item);
					}

					$related_field = $section->fetchFieldByHandle($field_handle);
					$related_field->appendFormattedElement($item, $relation);
				}
			}
		}

		return $element;
	}
}