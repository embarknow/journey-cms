<?php

namespace Embark\CMS\Schemas;

use Embark\CMS\Structures\ReferencedMetadataTrait;
use Embark\CMS\Structures\Guid;
use General;
use Symphony;

class Schema implements SchemaInterface
{
	use ReferencedMetadataTrait;

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
						e.schema_id = '%s'
				",
				[$this['guid']]
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
			if ($field['schema']['handle'] !== $handle) continue;

			return $field;
		}

		return false;
	}

	public function findFieldByGuid($guid)
	{
		foreach ($this['fields']->findAll() as $field) {
			if ($field->getGuid() !== $guid) continue;

			return $field;
		}

		return false;
	}
}
