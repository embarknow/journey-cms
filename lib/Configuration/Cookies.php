<?php

namespace Embark\Journey\Configuration;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;

use Embark\CMS\Metadata\Filters\Boolean;

class Cookies implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'prefix' => [
                'required' => false,
                'default' => 'journey_'
            ],
            'encrypt' => [
                'required' => true,
                'filter' => new Boolean
            ],
            'lifetime' => [
                'required' => true,
                'default' => '20 minutes' // We need to validate this somehow
            ],
            'path' => [
                'required' => true,
                'default' => '/'
            ],
            'domain' => [
                'required' => false,
            ],
            'secure' => [
                'required' => true,
                'filter' => new Boolean
            ],
            'httponly' => [
                'required' => true,
                'filter' => new Boolean
            ]
        ]);
    }
}
