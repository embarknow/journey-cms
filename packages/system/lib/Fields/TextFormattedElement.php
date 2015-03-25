<?php

namespace Embark\CMS\Fields;

use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use DOMElement;
use Entry;
use Exception;
use Field;

class TextFormattedElement implements MetadataInterface
{
	use MetadataTrait;
	use TextElementTrait;

	public function appendValue(DOMElement $element, Field $field, $data)
	{
		$document = $element->ownerDocument;
		$fragment = $document->createDocumentFragment();
		$element->setAttribute('mode', 'formatted');
		$value = $field->repairEntities($data->value_formatted);

		try {
			$fragment->appendXML($value);
		}

		catch (Exception $e) {
			$value = $field->repairMarkup($value);
			$fragment->appendXML($value);
		}

		$element->appendChild($fragment);
	}
}