<?php

namespace Embark\CMS\Actors\Section;

use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Database\Exception as DatabaseException;
use Embark\CMS\Structures\Resource;
use Embark\CMS\Structures\MetadataTrait;
use Embark\CMS\Structures\Pagination;
use Embark\CMS\Structures\QueryOptions;
use Embark\CMS\Structures\Sorting;
use Embark\CMS\SystemDateTime;
use Administration;
use Context;
use DOMElement;
use Duplicator;
use General;
use Field;
use Layout;
use MessageStack;
use Profiler;
use Section;
use SectionIterator;
use Symphony;
use SymphonyDOMElement;
use XMLDocument;
use Widget;

class Datasource implements DatasourceInterface
{
	use MetadataTrait;

	public function __construct()
	{
		$this->setSchema([
			'pagination' => [
				'type' =>		new Pagination()
			],
			'sorting' => [
				'type' =>		new Sorting()
			],
			'elements' => [
				'type' =>		new DatasourceOutputElements()
			],
			'parameters' => [
				'type' =>		new DatasourceOutputParameters()
			]
		]);
	}

	public function canExecute()
	{
		return true;
	}

	public function getType()
	{
		return __('Section Datasource');
	}

	public function createForm()
	{
		return new DatasourceForm($this);
	}

	public function createRenderer()
	{
		return new DatasourceRenderer($this);
	}

	public function appendColumns(DOMElement $wrapper)
	{
		$section = Section::loadFromHandle($this['section']);

		// Name:
		$wrapper->appendChild(Widget::TableData(Widget::Anchor(
			$this['name'],
			ADMIN_URL . "/blueprints/actors/edit/{$this['resource']['handle']}/"
		)));

		// Source:
		if ($section instanceof Section) {
			$wrapper->appendChild(Widget::TableData(Widget::Anchor(
				$section->name,
				ADMIN_URL . "/blueprints/sections/edit/{$section->handle}/"
			)));
		}

		else {
			$wrapper->appendChild(Widget::TableData(__('None'), [
				'class' =>	'inactive'
			]));
		}

		// Type:
		$wrapper->appendChild(Widget::TableData($this->getType()));
	}
}