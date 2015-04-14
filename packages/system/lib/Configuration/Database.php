<?php

namespace Embark\CMS\Configuration;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Metadata\Types\Vague;

/**
 * Vague is a defualt type where nesting may be required but not schema defined
 */
class Database implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([

        ]);
    }
}
