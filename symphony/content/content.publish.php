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
        }

        if ($type === 'view') {
            $this->{'__view' . $this->_context['page']}($section);
        }
    }

    public function __viewIndex($section)
    {
        $section->appendListView($this);
    }

    public function __viewNew($section)
    {
        $section->appendPublishView($this);
    }

    public function __viewEdit($section)
    {
        $section->appendPublishView($this, $this->_context['entry_id']);
    }
}
