<?php

namespace Embark\Journey\Routes;

use Embark\CMS\Metadata\ReferencedMetadataInterface;
use Embark\CMS\Metadata\ReferencedMetadataTrait;

use Embark\Journey\Metadata\Types\MiddlewareList;
use Embark\Journey\Routes\Route;

class RoutesList implements ReferencedMetadataInterface
{
    use ReferencedMetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'middleware' => [
                'type' => new MiddlewareList
            ],
            'route' => [
                'type' => new Route,
                'list' => true
            ]
        ]);
    }
}
