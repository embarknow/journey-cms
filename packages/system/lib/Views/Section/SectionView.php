<?php

namespace Embark\CMS\Views\Section;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Structures\MenuItem;
use HTMLDocument;

class SectionView implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'menu' => [
                'type' =>   new MenuItem()
            ],
            'table' => [
                'type' =>   new SectionTableView($this)
            ],
            'form' => [
                'type' =>   new SectionFormView($this)
            ]
        ]);
    }

    public function appendIndexView(HTMLDocument $page)
    {
        $this['table']->appendTable($page, $this);
    }

    public function appendFormView(HTMLDocument $page, EntryInterface $entry)
    {
        $this['form']->appendForm($page, $this, $entry);
    }
}
