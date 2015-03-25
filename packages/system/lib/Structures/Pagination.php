<?php

namespace Embark\CMS\Structures;

use Context;
use Datasource;
use DOMDocument;

class Pagination implements MetadataInterface {
	use MetadataTrait;

	public function __construct()
	{
		$this->setSchema([
			'append' => [
				'filter' =>			new Boolean()
			],
			'limit' => [
				'filter' =>			new Integer()
			],
			'page' => [
				'filter' =>			new Integer()
			],
			'entries-per-page' => [
				'filter' =>			new MaxInteger(1)
			],
			'current-page' => [
				'filter' =>			new MaxInteger(1)
			],
			'total-entries' => [
				'filter' =>			new Integer()
			],
			'total-pages' => [
				'filter' =>			new Integer()
			]
		]);
	}

	public function replaceParameters(Context $outputParameters)
	{
		$this['entries-per-page'] = Datasource::replaceParametersInString($this['limit'], $outputParameters);
		$this['current-page'] = Datasource::replaceParametersInString($this['page'], $outputParameters);

		$this['record-start'] = (
			(max(1, $this['current-page']) - 1) * $this['entries-per-page']
		);

		return $this;
	}

	public function setTotal($total)
	{
		$this['total-entries'] = (integer)$total;
		$this['total-pages'] = (integer)ceil($this['total-entries'] * (1 / $this['entries-per-page']));
	}

	public function createElement(DOMDocument $document)
	{
		$element = $document->createElement('pagination');

		$element->setAttribute('total-entries', $this['total-entries']);
		$element->setAttribute('total-pages', $this['total-pages']);
		$element->setAttribute('entries-per-page', $this['entries-per-page']);
		$element->setAttribute('current-page', $this['current-page']);

		return $element;
	}
}