<?php

namespace Embark\CMS\Fields\Link;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Entry;
use Symphony;

class Field implements MetadataInterface
{
	use MetadataTrait;

	public function __construct()
	{
		$this->setSchema([
			'related-fields' => [
				'type' =>		new RelatedFields()
			]
		]);
	}

	public function getParameterOutputValue($data, Entry $entry = null)
	{
		$result = [];

		if (!is_array($data)) {
			$data = array($data);
		}

		if (!empty($data)) foreach($data as $link) {
			if (is_null($link->relation_id)) continue;

			$result[] = $link->relation_id;
		}

		return $result;
	}

	public function loadDataFromDatabaseEntries($section, $entryIds)
	{
		try {
			$result = [];
			$rows = Symphony::Database()->query(
				"SELECT * FROM `data_%s_%s` WHERE `entry_id` IN (%s) ORDER BY `id` ASC",
				[
					$section,
					$this['name'],
					implode(',', $entryIds)
				]
			);

			foreach ($rows as $row) {
				$result[] = $row;
			}

			return $result;
		}

		catch (DatabaseException $e) {
			return [];
		}
	}
}
