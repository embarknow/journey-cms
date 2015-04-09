<?php

use Embark\CMS\Schemas\Controller as SchemaController;
use Embark\CMS\Sections\Controller as SectionController;

require_once LIB . '/class.administrationpage.php';

class ContentPublish extends AdministrationPage
{
	protected $section;

	public function __switchboard($type = 'view')
	{
		if (false === isset($section)) {
			$section = SectionController::read($this->_context['section_handle']);

			var_dump($section); exit;
		}

		if ($type === 'view') {
			$this->{'__view' . $this->_context['page']}($section);
		}
	}

	public function __viewIndex($section)
	{
		$section->appendIndexView($this);
	}

	public function __viewNew($section)
	{
		$section->appendFormView($this, new Entry());
	}

	public function __viewEdit($section)
	{
		$section->appendFormView($this, Entry::loadFromId($this->_context['entry_id']));
	}
}