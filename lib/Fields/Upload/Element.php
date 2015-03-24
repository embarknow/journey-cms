<?php

namespace Embark\CMS\Fields\Upload;

use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
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

		if (isset($data->name)) {
			$element->appendChild($document->createElement('file', $data->name, [
				'path'		=> trim($data->path, '/'),
				'name'		=> $data->file
			]));

			$meta = unserialize($data->meta);
			$metaXml = $document->createElement('meta');

			if (false === is_array($meta)) {
				$meta = [];
			}

			$meta['size'] = General::formatFilesize($data->size);
			$meta['type'] = $data->type;

			ksort($meta);

			foreach ($meta as $key => $value) {
				if ($key == 'creation' or $key == 'type') {
					$metaXml->setAttribute($key, $value);
				}

				else if ($key == 'size') {
					$bits = explode(' ', $value);

					if (count($bits) != 2) continue;

					$metaXml->appendChild($document->createElement(
						'size', $bits[0], array(
							'unit'	=> $bits[1]
						)
					));
				}

				else if (is_array($value)) {
					$metaXml->appendChild($document->createElement(
						$key, null, $value
					));
				}

				else {
					$metaXml->appendChild($document->createElement(
						$key, (string)$value
					));
				}
			}

			$element->appendChild($metaXml);
		}

		return $element;
	}
}