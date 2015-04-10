<?php

namespace Embark\CMS\Sections;

use Embark\CMS\Metadata\MetadataTrait;

class Section implements SectionInterface
{
	use MetadataTrait;

	public function __construct()
	{
		$this->setSchema([

		]);
	}
}
