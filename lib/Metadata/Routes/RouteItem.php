<?php

namespace Embark\Journey\Metadata\Routes;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;

use Embark\Journey\Metadata\Routes\RouteMethodsList;
use Embark\Journey\Metadata\Routes\RouteRedirect;

class RouteItem implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'methods' => [
                'required' => true,
                'type' => new RouteMethodsList
            ],
            'pattern' => [
                'required' => true,
                'default' => ''
            ],
            'name' => [
                'required' => true,
                'default' => ''
            ],
            'view' => [
                'required' => false,
                'default' => ''
            ],
            'redirect' => [
                'required' => false,
                'type' => new RouteRedirect
            ]
        ]);
    }
}
