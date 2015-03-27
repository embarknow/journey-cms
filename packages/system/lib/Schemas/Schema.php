<?php

namespace Embark\CMS\Schemas;

use Embark\CMS\Structures\MetadataTrait;
use Embark\CMS\Structures\Guid;
use Symphony;

class Schema implements SchemaInterface
{
	use MetadataTrait;

	public function __construct()
	{
		$this->setSchema([
			'guid' => [
				'required' =>	true,
				'filter' =>		new Guid(),
				'default' =>	uniqid()
			],
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
			if ($field['data']['handle'] !== $handle) continue;

			return $field;
		}

		return false;
	}
}