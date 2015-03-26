<?php

namespace Embark\CMS\Fields\Textbox;

use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Structures\Boolean;
use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use Embark\CMS\Schemas\Schema;
use DOMElement;
use Entry;
use Exception;
use Field;
use Section;

trait ElementTrait
{
	public function __construct()
	{
		$this->setSchema([
			'handle' => [
				'filter' =>		new Boolean()
			]
		]);
	}

	public function appendElement(DOMElement $wrapper, DatasourceInterface $datasource, Schema $section, Entry $entry)
	{
		$field = $section->findField($this['field']);

		if (false === $field) return;

		// var_dump($this['handle']); exit;

		$document = $wrapper->ownerDocument;
		$data = $entry->data()->{$this['field']};

		if (isset($data->value) || isset($data->value_formatted)) {
			$element = $document->createElement($this['field']);
			$wrapper->appendChild($element);

			try {
				$this->appendValue($element, $field, $data);
			}

			catch (Exception $e) {
				// Only get 'Document Fragment is empty' errors here.
			}

			if ($this['handle']) {
				$element->setAttribute('handle', $data->handle);
			}
		}

		return $element;
	}
}