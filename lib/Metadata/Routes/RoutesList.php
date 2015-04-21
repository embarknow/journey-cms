<?php

namespace Embark\Journey\Metadata\Routes;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;

use Embark\Journey\Metadata\Routes\Route;

class RoutesList implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'route' => [
                'type' => new Route,
                'list' => true
            ]
        ]);
    }
}
