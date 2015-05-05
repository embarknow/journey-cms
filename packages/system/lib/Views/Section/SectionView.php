<?php

namespace Embark\CMS\Views\Section;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Link;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Metadata\Types\MenuItem;
use Embark\CMS\Schemas\SchemaInterface;
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
            'filters' => [
                'type' =>   new SectionFilters()
            ],
            'list' => [
                'type' =>   new SectionListView()
            ],
            'form' => [
                'type' =>   new SectionFormView($this)
            ]
        ]);
    }

    public function appendListView(HTMLDocument $page)
    {
        $schema = $this['schema']->resolveInstanceOf(SchemaInterface::class);
        $pageLink = (new Link)->withPath(ADMIN_URL . '/publish/' . $this['resource']['handle']);

        $this['list']->appendListTo($page, $this, $schema, $pageLink);
    }

    public function appendPublishView(HTMLDocument $page, $entryId = null)
    {
        $schema = $this['schema']->resolveInstanceOf(SchemaInterface::class);
        $pageLink = (new Link)->withPath(ADMIN_URL . '/publish/' . $this['resource']['handle']);
        $entry = $schema->withEntry($entryId);

        $this['form']->appendHeaderTo($page, $this, $entry, $pageLink);
        $this['form']->appendFormTo($page, $this, $entry, $pageLink);
        $this['form']->appendFooterTo($page, $this, $entry, $pageLink);
    }
}
