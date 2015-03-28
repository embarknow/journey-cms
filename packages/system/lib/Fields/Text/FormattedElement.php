<?php

namespace Embark\CMS\Fields\Text;

use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldElementInterface;
use DOMElement;
use Exception;

class FormattedElement implements FieldElementInterface
{
    use ElementTrait;

    public function appendValue(DOMElement $element, FieldInterface $field, $data)
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
