<?php

namespace Embark\CMS\Fields;

use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use DOMElement;
use Entry;
use Exception;
use Field;

class TextUnformattedElement implements MetadataInterface
{
	use MetadataTrait;
	use TextElementTrait;

	public function appendValue(DOMElement $element, Field $field, $data)
	{
		$document = $element->ownerDocument;
		$element->setAttribute('mode', 'unformatted');
		$element->appendChild($document->createCDATASection($data->value));
	}
}