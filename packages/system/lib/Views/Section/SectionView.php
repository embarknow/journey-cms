<?php

namespace Embark\CMS\Views\Section;

use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use Embark\CMS\Structures\MenuItem;
use Widget;

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

    public function prepareIndex()
    {

    }

    public function prepareForm()
    {
        // $this['form']->prepareForm($this, $page);
    }

    public function viewIndex($page)
    {
        $page->setTitle(__('%1$s &ndash; %2$s', [__('Symphony'), $this['name']]));

        $page->appendSubheading(
            $this['name'],
            Widget::Anchor(
                __('Create New'),
                sprintf('%s/publish/%s/new', ADMIN_URL, $this['resource']['handle']),
                [
                    'title' => __('Create a new entry'),
                    'class' => 'create button'
                ]
            )
        );

        $this['table']->appendView($page->Form);
    }
}