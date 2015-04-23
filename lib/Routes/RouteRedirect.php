<?php

namespace Embark\Journey\Routes;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;

use Embark\CMS\Metadata\Filters\Enum;

class RouteRedirect implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'url' => [
                'required' => true,
                'default' => '/'
            ],
            'code' => [
                'required' => true,
                'filter' => new Enum([
                    301, 302, 303
                ])
            ],
        ]);
    }
}
