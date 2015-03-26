<?php

namespace Embark\CMS\Fields\Text;

use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use DOMElement;
use Entry;
use Exception;

class FormattedElement implements MetadataInterface
{
    use MetadataTrait;
    use ElementTrait;

    public function appendValue(DOMElement $element, Field $field, $data)
    {
        $document = $element->ownerDocument;
        $fragment = $document->createDocumentFragment();
        $element->setAttribute('mode', 'formatted');
        $value = $field->repairEntities($data->value_formatted);

        try {
            $fragment->appendXML($value);
        } catch (Exception $e) {
            $value = $field->repairMarkup($value);
            $fragment->appendXML($value);
        }

        $element->appendChild($fragment);
    }
}
