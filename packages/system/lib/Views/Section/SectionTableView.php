<?php

namespace Embark\CMS\Views\Section;

use Embark\CMS\Actors\Schemas\DatasourceQuery;
use Embark\CMS\Actors\Controller as ActorController;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldColumnInterface;
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
            'column' => [
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
        $handle = $view['resource']['handle'];
        $guid = $schema->getGuid();
        $actor = ActorController::read($view['actor']);
        $query = new DatasourceQuery();

        // Show only entries from this schema:
        $query->filterBySubQuery("select entry_id from entries where schema_id = '{$guid}'");

        // Sort entries by the selected column:
        if (isset($_GET['sort'], $_GET['direction'])) {
            $column = $this->findColumnByName($_GET['sort']);
            $column->appendSortingQuery($query, $schema, $_GET['direction']);

            $_SESSION["{$handle}.sort"] = $_GET['sort'];
            $_SESSION["{$handle}.direction"] = $_GET['direction'];

            redirect($url);
        }

        else if (isset($_SESSION["{$handle}.sort"], $_SESSION["{$handle}.direction"])) {
            $column = $this->findColumnByName($_SESSION["{$handle}.sort"]);
            $column->appendSortingQuery($query, $schema, $_SESSION["{$handle}.direction"]);
        }

        // Sort entries by the first column:
        else {
            $column = $this->findFirstColumn();
            $column->appendSortingQuery($query, $schema);
        }

        $this->appendHeader($page, $view);

        // Build table header:
        $table = $page->createElement('table');
        $page->Form->appendChild($table);

        $head = $page->createElement('thead');
        $table->appendChild($head);

        foreach ($this->findAllColumns() as $column) {
            $column->appendHeaderElement($head, $url);
        }

        if ($statement = $query->execute()) {
            $statement->bindColumn('entry_id', $entryId, PDO::PARAM_INT);

            while ($row = $statement->fetch(PDO::FETCH_BOUND)) {
                $entry = Entry::loadFromId($entryId);
                $row = $page->createElement('tr');
                $table->appendChild($row);

                foreach ($this->findAllColumns() as $column) {
                    $column->appendBodyElement($row, $schema, $entry, $url);
                }

                $input = Widget::Input('entry[]', $entry->entry_id, 'checkbox');
                $row->firstChild->appendChild($input);
            }
        }

        $this->appendFooter($page, $view);
    }

    public function findAllColumns()
    {
        foreach ($this->findAll() as $item) {
            if ($item instanceof FieldColumnInterface) {
                yield $item;
            }
        }
    }

    public function findFirstColumn()
    {
        foreach ($this->findAllColumns() as $item) {
            return $item;
        }

        return false;
    }

    public function findColumnByName($name)
    {
        foreach ($this->findAllColumns() as $item) {
            if ($item['name'] !== $name) continue;

            return $item;
        }

        return false;
    }
}