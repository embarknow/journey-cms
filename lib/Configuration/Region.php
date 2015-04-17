<?php

namespace Embark\Journey\Configuration;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Metadata\Filters\Boolean;
use Embark\CMS\Metadata\Filters\Enum;

use Embark\Journey\Metadata\Filters\Semver;

class Region implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'time-format' => [
                'required' => true,
                // 'filter' => new DateTimeFormat
            ],
            'date-format' => [
                'required' => true,
                // 'filter' => new DateTimeFormat
            ],
            'datetime-separator' => [
                'required' => true,
                'default' => ' '
            ],
            'timezone' => [ // Can we validate a timezone with a filter class?
                'required' => true,
                'default' => 'UTC'
            ]
        ]);
    }
}
