<?php

namespace Embark\Journey\Metadata\Routes;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;

class RouteGroupItem implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'prefix' => [
                'required' => true,
                'default' => '/'
            ],
            'routes' => [
                'required' => true,
                'default' => ''
            ],
        ]);
    }
}
