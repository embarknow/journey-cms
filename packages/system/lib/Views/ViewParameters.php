<?php

namespace Embark\CMS\Views;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Views\ViewParameter;

class ViewParameters implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'item' => [
                'type' => new ViewParameter
            ]
        ]);
    }
}
