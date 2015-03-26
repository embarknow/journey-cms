<?php

namespace Embark\CMS\Structures;

class Guid implements MetadataValueInterface
{
	public function toXML($value)
	{
		return $this->sanitise($value);
	}

	public function fromXML($value)
	{
		return $this->sanitise($value);
	}

	public function sanitise($value)
	{
		return (
			isset($value)
				? $value
				: uniqid()
		);
	}
}