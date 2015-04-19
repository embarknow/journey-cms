<?php

namespace Embark\Journey\Metadata\Configuration;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;

use Embark\CMS\Metadata\Filters\Integer;

class Domain implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'driver' => [
                'required' => true,
                'default' => 'mysql'
            ],
            'host' => [
                'required' => true,
                'default' => 'localhost'
            ],
            'port' => [
                'required' => true,
                'filter' => new Integer
            ],
            'dbname' => [
                'required' => true,
                'default' => 'journey_cms'
            ],
            'user' => [
                'required' => true
            ],
            'password' => [
                'required' => true
            ]
        ]);
    }
}
