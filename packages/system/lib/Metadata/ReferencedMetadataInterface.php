<?php

namespace Embark\CMS\Metadata;

interface ReferencedMetadataInterface extends MetadataInterface
{
    public function getGuid();
    public function setGuid($guid);
}
