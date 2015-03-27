<?php

namespace Embark\CMS\Schemas;

use Embark\CMS\Structures\MetadataInterface;

interface SchemaInterface extends MetadataInterface
{
	public function countEntries();

	public function findField($handle);
}