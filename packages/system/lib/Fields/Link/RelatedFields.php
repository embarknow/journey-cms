<?php

namespace Embark\CMS\Fields\Link;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Fields\Link\RelatedField;
use Entry;
use Symphony;

class RelatedFields implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'item' => [
                'type' => new RelatedField()
            ]
        ]);
    }
}
