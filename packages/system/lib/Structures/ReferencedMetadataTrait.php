<?php

namespace Embark\CMS\Structures;

trait ReferencedMetadataTrait
{
    use MetadataTrait;

    protected $metadataGuid;

    protected function createGuid()
    {
        $this->metadataGuid = uniqid(true);
    }

    public function getGuid()
    {
        if (false === isset($this->metadataGuid)) {
            $this->createGuid();
        }

        return $this->metadataGuid;
    }

    public function setGuid($guid)
    {
        $this->metadataGuid = $guid;
    }
}