<?php

namespace Embark\Journey\Metadata\Routes;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;

use Embark\CMS\Metadata\Filters\Enum;

class RouteMethodsList implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'item' => [
                'filter' => new Enum([
                    'GET', 'HEAD', 'POST', 'PUT', 'DELETE'
                ]),
            ]
        ]);
    }
}
