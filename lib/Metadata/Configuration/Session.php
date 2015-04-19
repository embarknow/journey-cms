<?php

namespace Embark\Journey\Metadata\Configuration;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;

use Embark\CMS\Metadata\Filters\Boolean;
use Embark\CMS\Metadata\Filters\Integer;

class Session implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'name' => [
                'required' => true,
                'default' => 'journey-session'
            ],
            'gc-probability' => [
                'required' => true,
                'filter' => new Integer,
                'default' => 1
            ],
            'gc-divisor' => [
                'required' => true,
                'filter' => new Integer,
                'default' => 100
            ],
            'gc-maxlifetime' => [
                'required' => true,
                'filter' => new Integer,
                'default' => 1440
            ],
            'cookie-lifetime' => [
                'required' => true,
                'filter' => new Integer,
                'default' => 1440
            ],
            'cookie-path' => [
                'required' => true,
                'default' => '/'
            ],
            'cookie-domain' => [
                'required' => false,
                'default' => ''
            ],
            'cookie-secure' => [
                'required' => true,
                'filter' => new Boolean,
                'default' => false
            ],
            'cookie-httponly' => [
                'required' => true,
                'filter' => new Boolean,
                'default' => false
            ],
            'encrypt' => [
                'required' => true,
                'filter' => new Boolean,
                'default' => false
            ],
            'flash-key' => [
                'required' => true,
                'default' => 'flash'
            ]
        ]);
    }
}
