<?php

namespace Embark\Journey\Routes;

use Embark\CMS\Metadata\ReferencedMetadataInterface;
use Embark\CMS\Metadata\ReferencedMetadataTrait;

use Embark\Journey\Routes\Route;

class RoutesList implements ReferencedMetadataInterface
{
    use ReferencedMetadataTrait;

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
