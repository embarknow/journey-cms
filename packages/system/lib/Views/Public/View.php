<?php

namespace Embark\CMS\Views\Public;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Metadata\Filters\Guid;
use Embark\CMS\Views\ViewParameters;
use Embark\CMS\Views\ViewTypes;

class View implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'title' => [
                'required' => true,
                'default' => ''
            ],
            'handle' => [
                'required' => true
                'default' => ''
            ],
            'content-type' => [
                'required' => true
                'default' => 'text/html;charset=utf-8'
            ],
            'parameters' => [
                'required' => false,
                'type' => new ViewParameters
            ],
            'types' => [
                'required' => false,
                'type' => new ViewTypes
            ],
            'template' => [
                'required' => true,
                'type' => new ViewTemplate
            ]
        ]);
    }
}
