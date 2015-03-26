<?php

namespace Embark\CMS\Fields\Text;

use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use DOMElement;
use Entry;
use Exception;

class UnformattedElement implements MetadataInterface
{
	use MetadataTrait;
	use ElementTrait;

	public function appendValue(DOMElement $element, Field $field, $data)
	{
		$document = $element->ownerDocument;
		$element->setAttribute('mode', 'unformatted');
		$element->appendChild($document->createCDATASection($data->value));
	}
}