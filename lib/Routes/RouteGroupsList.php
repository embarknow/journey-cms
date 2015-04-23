<?php

namespace Embark\Journey\Routes;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Metadata\Filters\Boolean;
use Embark\CMS\Metadata\Filters\Integer;

use Embark\Journey\Routes\RouteGroupItem;

class RouteGroupsList implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'group' => [
                'required' => true,
                'list' => true,
                'type' => new RouteGroupItem
            ],
        ]);
    }
}
