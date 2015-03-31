<?php

namespace Embark\CMS\Views\Section;

use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Schemas\Controller as SchemaController;
use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use DOMElement;
use HTMLDocument;
use Widget;

class SectionTableView implements MetadataInterface
{
    use MetadataTrait;

    public function __construct(SectionView $view)
    {
        $this->setSchema([
            'field' => [
                'list' =>   true
            ]
        ]);
    }

    protected function appendHeader(HTMLDocument $page, SectionView $view)
    {
        $url = ADMIN_URL . '/publish/' . $view['resource']['handle'];

        $page->setTitle(__('%1$s &ndash; %2$s', [
            __('Symphony'), $view['name']
        ]));
        $page->appendBreadcrumb(Widget::Anchor($view['name'], $url));
        $page->appendButton(Widget::Anchor(
            __('Create New'),
            $url . '/new',
            [
                'title' => __('Create a new entry'),
                'class' => 'create button'
            ]
        ));
        $page->Form->setAttribute('enctype', 'multipart/form-data');
    }

    protected function appendFooter(HTMLDocument $page, SectionView $view)
    {
    }

    public function appendTable(HTMLDocument $page, SectionView $view)
    {
        $url = ADMIN_URL . '/publish/' . $view['resource']['handle'];
        $schema = SchemaController::read($view['schema']);

        $this->appendHeader($page, $view);

        $table = $page->createElement('table');
        $page->appendChild($table);

        $head = $page->createElement('thead');
        $table->appendChild($head);

        foreach ($this->findAll() as $item) {
            if ($item instanceof FieldInterface) {
                if (isset($item['schema']['guid'])) {
                    $field = $schema->findFieldByGuid($item['schema']['guid']);

                    if ($field instanceof FieldInterface) {
                        $item->fromMetadata($field);
                    }
                }

                $item['column']->appendHeader($head);
            }
        }

        $this->appendFooter($page, $view);

        // var_dump($this); exit;

        // foreach ($this->findAll() as $column) {
        //     if (isset($column['field']['column'])) {
        //         // var_dump($column); exit;
        //         $column->appendHeader($head);
        //     }
        // }
    }
}