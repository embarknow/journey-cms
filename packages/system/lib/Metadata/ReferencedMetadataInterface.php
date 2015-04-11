<?php

namespace Embark\CMS\Metadata;

use Embark\CMS\Metadata\MetadataInterface;

interface ReferencedMetadataInterface extends MetadataInterface
{
    public function getGuid();
    public function setGuid($guid);
}
