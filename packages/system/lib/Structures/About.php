<?php

namespace Embark\CMS\Structures;

use Embark\CMS\Structures\Author;

class About implements MetadataInterface {
	use MetadataTrait;

	public function __construct()
	{
		$this->setSchema([
			'name' => [
				'required' =>	true,
				'default' =>	''
			],
			'author' =>	[
				'required' =>	true,
				'type' =>		new Author()
			]
		]);
	}
}