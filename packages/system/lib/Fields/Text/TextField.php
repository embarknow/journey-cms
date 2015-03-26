<?php

namespace Embark\CMS\Fields\Text;

use Embark\CMS\Database\Exception as DatabaseException;
use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use Embark\CMS\Structures\Integer;
use Entry;
use Context;
use Symphony;
use Tidy;

/**
 * Defines the relationship between a section and it's data.
 */
class TextField implements MetadataInterface
{
	use MetadataTrait;

	public function __construct()
	{
		$this->setSchema([
			'max-length' => [
				'filter' =>		new Integer()
			]
		]);
	}

	public function getParameterOutputValue($data, Entry $entry = null)
	{
		return $data->handle;
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

	public function repairEntities($value)
	{
		return preg_replace('/&(?!(#[0-9]+|#x[0-9a-f]+|amp|lt|gt);)/i', '&amp;', trim($value));
	}

	public function repairMarkup($value)
	{
		$tidy = new Tidy();
		$tidy->parseString(
			$value, array(
				'drop-font-tags'				=> true,
				'drop-proprietary-attributes'	=> true,
				'enclose-text'					=> true,
				'enclose-block-text'			=> true,
				'hide-comments'					=> true,
				'numeric-entities'				=> true,
				'output-xhtml'					=> true,
				'wrap'							=> 0,

				// HTML5 Elements:
				'new-blocklevel-tags'			=> 'section nav article aside hgroup header footer figure figcaption ruby video audio canvas details datagrid summary menu',
				'new-inline-tags'				=> 'time mark rt rp output progress meter',
				'new-empty-tags'				=> 'wbr source keygen command'
			), 'utf8'
		);

		return $tidy->body()->value;
	}
}