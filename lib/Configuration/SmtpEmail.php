<?php

namespace Embark\Journey\Configuration;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;

use Embark\CMS\Metadata\Filters\Boolean;
use Embark\CMS\Metadata\Filters\Integer;

class SmtpEmail implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'default-gateway' => [
                'required' => true,
                'default' => 'smtp'
            ],
            'from-name' => [
                'required' => true,
                'default' => 'Journey CMS'
            ],
            'from-address' => [
                'required' => true,
                'default' => 'noreply@' // Any addresses with no domain should prefix current domain
            ],
            'host' => [
                'required' => true,
                'default' => '127.0.0.1'
            ],
            'port' => [
                'required' => true,
                'filter' => new Integer,
                'default' => 25
            ],
            'secure' => [
                'required' => true,
                'filter' => new Boolean,
                'default' => false
            ],
            'auth' => [
                'required' => true,
                'filter' => new Integer,
                'default' => 0
            ],
            'username' => [
                'required' => true,
                'default' => 'admin'
            ],
            'password' => [
                'required' => true,
                'default' => 'admin'
            ]
        ]);
    }
}
