<?php

namespace Embark\CMS\Schemas;

use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use Symphony;

class Schema implements MetadataInterface
{
	use MetadataTrait;

	public function __construct()
	{
		$this->setSchema([
			'fields' => [
				'required' =>	true,
				'type' =>		new FieldsList()
			]
		]);
	}

	public function countEntries()
	{
		try {
			$result = Symphony::Database()->query(
				"
					SELECT
						count(*) AS `count`
					FROM
						`entries` AS e
					WHERE
						e.section = '%s'
				",
				[$this['resource']['handle']]
			);

			if ($result->valid()) {
				return (integer)$result->current()->count;
			}
		}

		catch (Exception $e) {
			return 0;
		}

		return 0;
	}

	public function findField($handle)
	{
		foreach ($this['fields']->findAll() as $field) {
			if ($field['name'] !== $handle) continue;

			return $field;
		}

		return false;
	}
}