<?php

namespace Embark\CMS\Schemas;

use Embark\CMS\Metadata\ReferencedMetadataInterface;

interface SchemaInterface extends ReferencedMetadataInterface
{
    public function countEntries();

    public function deleteEntries(array $entries);

    public function findField($handle);
}
