<?php

namespace Embark\CMS\Views;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;

class ViewParameter implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'name' => [
                'required' => true,
                'default' => ''
            ],
            'default-value' => [
                'required' => false,
                'default' => ''
            ]
        ]);
    }
}
