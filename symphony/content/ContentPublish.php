<?php

use AdministrationPage;
use Entry;

use Embark\CMS\Sections\Controller as Sections;

class ContentPublish extends AdministrationPage
{
    protected $section;

    public function __switchboard($type = 'view')
    {
        if (false === isset($section)) {
            $section = Sections::read($this->_context['section_handle']);
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
        $section->appendPublishView($this, new Entry());
    }

    public function __viewEdit($section)
    {
        $section->appendPublishView($this, Entry::loadFromId($this->_context['entry_id']));
    }
}
