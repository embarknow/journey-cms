<?php

namespace Embark\Journey\Metadata\Types;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;

use Embark\Journey\Metadata\Types\MiddlewareItem;

class MiddlewareList implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'item' => [
                'type' => new MiddlewareItem
            ]
        ]);
    }
}
