<?php

namespace Embark\CMS\Views\Section;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Link;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Metadata\Types\MenuItem;
use Embark\CMS\Schemas\Controller as SchemaController;
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
        $schema = SchemaController::read($this['schema']);
        $pageLink = (new Link)->withPath(ADMIN_URL . '/publish/' . $this['resource']['handle']);

        $this['table']->appendHeaderTo($page, $this, $schema, $pageLink);
        $this['table']->appendListTo($page, $this, $schema, $pageLink);
        $this['table']->appendFooterTo($page, $this, $schema, $pageLink);
    }

    public function appendFormView(HTMLDocument $page, EntryInterface $entry)
    {
        $schema = SchemaController::read($this['schema']);
        $pageLink = (new Link)->withPath(ADMIN_URL . '/publish/' . $this['resource']['handle']);

        $this['form']->appendHeaderTo($page, $this, $schema, $entry, $pageLink);
        $this['form']->appendFormTo($page, $this, $schema, $entry, $pageLink);
        $this['form']->appendFooterTo($page, $this, $schema, $entry, $pageLink);
    }
}
