<?php

namespace Embark\CMS\Fields\System;

use Embark\CMS\Structures\Boolean;
use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use DOMElement;
use Widget;

class CreationDateColumn implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'editLink' => [
                'filter' =>     new Boolean()
            ]
        ]);
    }

    public function appendHeader(DOMElement $wrapper)
    {
        $wrapper->appendChild(Widget::TableColumn([
            $this['name'], 'col'
        ]));
    }
}