<?php

namespace Embark\CMS\Structures;

class MaxInteger implements MetadataValueInterface
{
	protected $max;

	public function __construct($max)
	{
		$this->max = (integer)$max;
	}

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
		return max($this->max, (integer)$value);
	}
}