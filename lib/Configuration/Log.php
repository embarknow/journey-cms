<?php

namespace Embark\Journey\Configuration;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Metadata\Filters\Boolean;
use Embark\CMS\Metadata\Filters\Enum;

use Embark\Journey\Metadata\Filters\Semver;

class Log implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'enabled' => [
                'required' => true,
                'filter' => new Boolean
            ],
            'level' => [
                'required' => true,
                'filter' => new Enum('DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY')
            ],
            'name' => [
                'required' => true,
                'default' => 'main'
            ]
        ]);
    }
}
