<?php

namespace Embark\Journey\Configuration;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Metadata\Filters\Boolean;
use Embark\CMS\Metadata\Filters\Integer;

use Embark\Journey\Metadata\Filters\Semver;

class Core implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'name' => [
                'required' => true,
                'default' => 'Journey CMS'
            ],
            'version' => [
                'required' => true,
                'filter' => new Semver
            ],
            'debug' => [
                'required' => true,
                'filter' => new Boolean
            ]
        ]);
    }
}
