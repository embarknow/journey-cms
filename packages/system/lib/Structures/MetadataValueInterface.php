<?php

namespace Embark\CMS\Structures;

interface MetadataValueInterface
{
	public function toXML($value);
	public function fromXML($value);
}