<?php

namespace Embark\CMS\Fields\Link;

use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use Entry;
use Symphony;

class RelatedFields implements MetadataInterface
{
	use MetadataTrait;

	public function __construct()
	{
		$this->setSchema([
			'item' => [
				'type' =>		new RelatedField()
			]
		]);
	}
}