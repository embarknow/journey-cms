<?php

namespace Embark\CMS\Structures;

class Boolean implements MetadataValueInterface
{
	public function toXML($value)
	{
		return ($value ? 'yes' : 'no');
	}

	public function fromXML($value)
	{
		return ($value === 'yes');
	}
}