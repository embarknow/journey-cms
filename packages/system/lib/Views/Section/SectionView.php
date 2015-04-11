<?php

namespace Embark\CMS\Views\Section;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Schemas\Controller as SchemaController;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Metadata\Types\MenuItem;
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

        $this['table']->appendHeader($page, $this, $schema);
        $this['table']->appendTable($page, $this, $schema);
        $this['table']->appendFooter($page, $this, $schema);
    }

    public function appendFormView(HTMLDocument $page, EntryInterface $entry)
    {
        $schema = SchemaController::read($this['schema']);

        $this['form']->appendHeader($page, $this, $schema, $entry);
        $this['form']->appendForm($page, $this, $schema, $entry);
        $this['form']->appendFooter($page, $this, $schema, $entry);
    }
}
