<?php

namespace Embark\CMS\Metadata\Types;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;

/**
 * Vague is a defualt type where nesting may be required but not schema defined
 */
class Vague implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([

        ]);
    }
}
