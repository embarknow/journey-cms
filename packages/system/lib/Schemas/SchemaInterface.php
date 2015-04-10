<?php

namespace Embark\CMS\Schemas;

use Embark\CMS\Structures\ReferencedMetadataInterface;

interface SchemaInterface extends ReferencedMetadataInterface
{
	public function countEntries();

	public function findField($handle);
}