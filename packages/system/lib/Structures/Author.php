<?php

namespace Embark\CMS\Structures;

use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;

class Author implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'name' => [
                'required' => true,
                'default' =>  ''
            ],
            'website' => [
                'required' => true,
                'default' =>  ''
            ],
            'email' => [
                'required' => true,
                'default' =>  ''
            ]
        ]);
    }
}
