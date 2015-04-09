<?php

namespace Embark\CMS\Structures;

interface ReferencedMetadataInterface extends MetadataInterface
{
    public function getGuid();
    public function setGuid($guid);
}
