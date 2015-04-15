<?php

namespace Embark\CMS\Configuration;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Metadata\Filters\Boolean;
use Embark\CMS\Metadata\Filters\Integer;

class Logging implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'archive' => [
                'required' => true,
                'filter' => new Boolean
            ],
            'maxsize' => [
                'required' => true,
                'filter' => new Integer
            ]
        ]);
    }
}
