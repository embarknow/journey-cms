<?php

namespace Embark\CMS\Configuration;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Metadata\Types\Vague;

/**
 * Vague is a defualt type where nesting may be required but not schema defined
 */
class Main implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'admin' => [
                'required' => true,
                'type' => new Admin
            ],
            'region' => [
                'required' => true,
                'type' => new Region
            ],
            'logging' => [
                'required' => true,
                'type' => new Logging
            ],
            'session' => [
                'required' => true,
                'type' => new Session
            ],
            'system' => [
                'required' => true,
                'type' => new System
            ]
        ]);
    }
}
