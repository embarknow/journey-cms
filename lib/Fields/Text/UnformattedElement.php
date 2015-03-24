<?php

namespace Embark\CMS\Fields\Text;

use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use DOMDocument;
use DOMElement;
use Entry;
use Exception;
use Field;

class UnformattedElement implements MetadataInterface
{
	use MetadataTrait;
	use ElementTrait;

	public function appendValue(DOMDocument $document, DOMElement $element, Field $field, $data)
	{
		$element->setAttribute('mode', 'unformatted');
		$element->appendChild($document->createCDATASection($data->value));
	}
}