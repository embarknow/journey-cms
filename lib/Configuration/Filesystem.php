<?php

namespace Embark\Journey\Configuration;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Metadata\Filters\Boolean;
use Embark\CMS\Metadata\Filters\Integer;

use Embark\Journey\Metadata\Filters\Semver;

class Filesystem implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'file' => [
                'required' => true,
                'filter' => new ZerofillInteger
            ],
            'directory' => [
                'required' => true,
                'filter' => new ZerofillInteger
            ],
            'max-upload-size' => [
                'required' => true,
                'filter' => new Integer
            ]
        ]);
    }
}
