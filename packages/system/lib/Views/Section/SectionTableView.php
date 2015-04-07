<?php

namespace Embark\CMS\Views\Section;

use Embark\CMS\Actors\Schemas\DatasourceQuery;
use Embark\CMS\Actors\Controller as ActorController;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Schemas\Controller as SchemaController;
use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use DOMElement;
use Entry;
use HTMLDocument;
use PDO;
use Symphony;
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
        $actor = ActorController::read($view['actor']);
        $query = new DatasourceQuery();

        // Prepare column fields by importing data from the schema:
        foreach ($this->findAll() as $item) {
            if ($item instanceof FieldInterface) {
                if (isset($item['schema']['guid'])) {
                    $field = $schema->findFieldByGuid($item['schema']['guid']);

                    if ($field instanceof FieldInterface) {
                        $item->fromMetadata($field);
                    }
                }
            }
        }

        // Sort entries by the selected column:
        if (isset($_GET['sort'], $_GET['direction'])) {
            $field = $this->findColumnByName($_GET['sort']);

            if ($field['sorting'] instanceof MetadataInterface) {
                $field['sorting']['direction'] = $_GET['direction'];
                $field['sorting']->appendQuery($query, $schema, $field);
            }
        }

        // Sort entries by the default sorting:
        foreach ($this->findAll() as $item) {
            if ($item instanceof FieldInterface) {
                if ($item['sorting'] instanceof MetadataInterface) {
                    $item['sorting']->appendQuery($query, $schema, $item);
                }
            }
        }

        $this->appendHeader($page, $view);

        // Build table header:
        $table = $page->createElement('table');
        $page->Form->appendChild($table);

        $head = $page->createElement('thead');
        $table->appendChild($head);

        foreach ($this->findAll() as $item) {
            if ($item instanceof FieldInterface) {
                $item['column']->appendHeader($head, $schema, $item, $url);
            }
        }

        if ($statement = $query->execute()) {
            $statement->bindColumn('entry_id', $entryId, PDO::PARAM_INT);

            while ($row = $statement->fetch(PDO::FETCH_BOUND)) {
                $entry = Entry::loadFromId($entryId);
                $row = $page->createElement('tr');
                $table->appendChild($row);

                foreach ($this->findAll() as $item) {
                    if ($item instanceof FieldInterface) {
                        $item['column']->appendBody($row, $schema, $entry, $item, $url);
                    }
                }

                $input = Widget::Input('entry[]', $entry->entry_id, 'checkbox');
                $row->firstChild->appendChild($input);
            }
        }

        $this->appendFooter($page, $view);
    }

    public function findColumnByName($name)
    {
        foreach ($this->findAll() as $item) {
            if ($item instanceof FieldInterface) {
                if ($item['column']['name'] !== $name) continue;

                return $item;
            }
        }

        return false;
    }
}