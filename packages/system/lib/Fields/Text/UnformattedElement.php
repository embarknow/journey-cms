<?php

namespace Embark\CMS\Fields\Text;

use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldElementInterface;
use DOMElement;

class UnformattedElement implements FieldElementInterface
{
    use ElementTrait;

    public function appendValue(DOMElement $element, FieldInterface $field, $data)
    {
        $document = $element->ownerDocument;
        $element->setAttribute('mode', 'unformatted');
        $element->appendChild($document->createCDATASection($data->value));
    }
}
