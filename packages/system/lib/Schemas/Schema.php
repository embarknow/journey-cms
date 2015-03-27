<?php

namespace Embark\CMS\Schemas;

use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use Embark\CMS\Structures\Guid;
use Symphony;

class Schema implements MetadataInterface
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
			if ($field['name'] !== $handle) continue;

			return $field;
		}

		return false;
	}



	public function syncroniseStatistics(Section $section) {
		$new_doc = new DOMDocument('1.0', 'UTF-8');
		$new_doc->formatOutput = true;
		$new_doc->loadXML((string)$section);
		$new_xpath = new DOMXPath($new_doc);
		$new_handle = $section->handle;

		$old = $new = array();
		$result = (object)array(
			'synced'	=> true,
			'section'	=> (object)array(
				'create'	=> false,
				'rename'	=> false,
				'old'		=> (object)array(
					'handle'	=> $new_handle,
					'name'		=> $section->name
				),
				'new'		=> (object)array(
					'handle'	=> $new_handle,
					'name'		=> $section->name
				)
			),
			'remove'	=> array(),
			'rename'	=> array(),
			'create'	=> array(),
			'update'	=> array()
		);

		$res = Symphony::Database()->query(
			'
				SELECT
					s.xml
				FROM
					`sections_sync` AS s
				WHERE
					s.section = "%s"
			',
			array(
				$section->guid
			)
		);

		// Found sync data:
		if ($res->valid()) {
			$old_doc = new DOMDocument('1.0', 'UTF-8');
			$old_doc->formatOutput = true;
			$old_doc->loadXML($res->current()->xml);
			$old_xpath = new DOMXPath($old_doc);
			$old_handle = $old_xpath->evaluate('string(/section/name/@handle)');

			if ($old_handle != $new_handle) {
				$result->synced = false;
				$result->section->rename = true;
				$result->section->old->handle = $old_handle;
				$result->section->old->name = $old_xpath->evaluate('string(/section/name)');
			}

			// Build array of old and new nodes for comparison:
			foreach ($old_xpath->query('/section/fields/field') as $node) {
				$type = $old_xpath->evaluate('string(type)', $node);
				$field = Field::loadFromType($type);
				$field->loadSettingsFromSimpleXMLObject(
					simplexml_import_dom($node)
				);

				$old[$field->guid] = (object)array(
					'label'		=> $field->{'publish-label'},
					'field'		=> $field
				);
			}
		}

		// Creating the section:
		else {
			$result->synced = false;
			$result->section->create = true;
		}

		foreach ($new_xpath->query('/section/fields/field') as $node) {
			$type = $new_xpath->evaluate('string(type)', $node);
			$field = Field::loadFromType($type);
			$field->loadSettingsFromSimpleXMLObject(
				simplexml_import_dom($node)
			);

			$new[$field->guid] = (object)array(
				'label'		=> $field->{'publish-label'},
				'field'		=> $field
			);
		}

		foreach ($new as $guid => $data) {
			// Field is being created:
			if (array_key_exists($guid, $old) === false) {
				$result->create[$guid] = $data;
				continue;
			}

			// Field is being renamed:
			if ($result->section->rename or $old[$guid]->field->{'element-name'} != $data->field->{'element-name'}) {
				if ($old[$guid]->field->type == $data->field->type) {
					$result->rename[$guid] = (object)array(
						'label'		=> $data->{'label'},
						'old'		=> $old[$guid]->field,
						'new'		=> $data->field
					);
				}

				// Type has changed:
				else {
					$result->remove[$guid] = $old[$guid];
					$result->create[$guid] = $data;
					continue;
				}
			}

			// Field definition has changed:
			if ($old[$guid]->field != $data->field) {
				if ($old[$guid]->field->type == $data->field->type) {
					$result->update[$guid] = (object)array(
						'label'		=> $data->{'label'},
						'old'		=> $old[$guid]->field,
						'new'		=> $data->field
					);
				}

				// Type has changed:
				else {
					$result->remove[$guid] = $old[$guid];
					$result->create[$guid] = $data;
					continue;
				}
			}
		}

		foreach ($old as $guid => $data) {
			if (array_key_exists($guid, $new)) continue;

			$result->remove[$guid] = $data;
		}

		$result->synced = (
			$result->synced
			and empty($result->remove)
			and empty($result->rename)
			and empty($result->create)
			and empty($result->update)
		);

		return $result;
	}
}