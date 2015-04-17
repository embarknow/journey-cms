<?php

namespace Embark\Journey\Configuration;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Metadata\Filters\Boolean;
use Embark\CMS\Metadata\Filters\Integer;

use Embark\Journey\Metadata\Types\RoutesList;

class Admin implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'path' => [
                'required' => true,
                'default' => 'journey'
            ],
            'pagination' => [
                'required' => true,
                'filter' => new Integer
            ],
            'minify-assets' => [
                'required' => true,
                'filter' => new Boolean
            ],
            'locale' => [
                'required' => true,
                'default' => 'en'
            ],
            'routes' => [
                'required' => true,
                'type' => new RoutesList
            ]
        ]);
    }
}
