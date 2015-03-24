<?php

namespace Embark\CMS\Actors;

use Embark\CMS\Structures\MetadataInterface;
use DOMDocument;

class Controller {
	public static function fromXML(DOMDocument $document)
	{
		$element = $document->documentElement;
		$type = '\\' . $element->getAttribute('type');
		$metadata = new $type;
		$metadata->fromXML($element);
		$metadata->fromDefaults();

		return $metadata;
	}

	public static function toXML(MetadataInterface $metadata)
	{
		$document = new DOMDocument();
		$root = $document->createElement('object');
		$root->setAttribute('type', get_class($metadata));
		$document->appendChild($root);
		$metadata->toXML($root);

		return $document;
	}
}