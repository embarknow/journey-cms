<?php

namespace Embark\CMS\Structures;

use Context;
use Datasource;
use DOMDocument;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;

class Sorting implements MetadataInterface
{
    use MetadataTrait;

    const RANDOM = 'random';

    public function __construct()
    {
        $this->setSchema([
            'append' => [
                'filter' => new Boolean()
            ],
            'field' => [
                'list' =>   true
            ]
        ]);
    }

    public function replaceParameters(Context $outputParameters)
    {
        $this['direction'] = Datasource::replaceParametersInString($this['direction'], $outputParameters);

        return $this;
    }

    public function createElement(DOMDocument $document)
    {
        $element = $document->createElement('sorting');

        $element->setAttribute('field', $this['field']);
        $element->setAttribute('direction', $this['direction']);

        return $element;
    }
}
