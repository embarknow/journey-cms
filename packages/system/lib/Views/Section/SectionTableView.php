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
        // Delete entries and then redirect:
        if (isset($_POST['entries'], $_POST['with-selected']) && 'delete' === $_POST['with-selected']) {
            $url = ADMIN_URL . '/publish/' . $view['resource']['handle'];
            $schema = SchemaController::read($view['schema']);

            Symphony::Database()->beginTransaction();

            try {
                foreach ($_POST['entries'] as $entryId) {
                    $entry = Entry::loadFromId($entryId);

                    // Delete all field data:
                    foreach ($schema->findAllFields() as $field) {
                        $field['data']->delete($schema, $entry, $field);
                    }

                    // Delete the entry record:
                    $statement = Symphony::Database()->prepare("
                        delete from `entries` where
                            entry_id = :entryId
                    ");

                    $statement->execute([
                        ':entryId' =>   $entryId
                    ]);
                }

                Symphony::Database()->commit();

                redirect($url);
            }

            // Something went wrong, do not commit:
            catch (\Exception $error) {
                Symphony::Database()->rollBack();

                throw $error;
            }
        }

        $actions = $page->createElement('div');
        $actions->setAttribute('class', 'actions');
        $page->Form->appendChild($actions);

        $options = [
            array(NULL, false, __('With Selected...')),
            array('delete', false, __('Delete'))
        ];

        $actions->appendChild(Widget::Select('with-selected', $options));
        $actions->appendChild(Widget::Input('action[apply]', __('Apply'), 'submit'));
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

        if ($statement = $query->execute() and $statement->rowCount()) {
            $statement->bindColumn('entry_id', $entryId, PDO::PARAM_INT);

            while ($row = $statement->fetch(PDO::FETCH_BOUND)) {
                $entry = Entry::loadFromId($entryId);
                $row = $page->createElement('tr');
                $table->appendChild($row);

                foreach ($this->findAllColumns() as $column) {
                    $column->appendBodyElement($row, $schema, $entry, $url);
                }

                $input = Widget::Input('entries[]', $entry->entry_id, 'checkbox');
                $row->firstChild->appendChild($input);
            }
        }

        else {
            $colspan = count(iterator_to_array($this->findAllColumns()));

            $row = $page->createElement('tr');
            $table->appendChild($row);

            $cell = $page->createElement('td');
            $cell->setAttribute('colspan', $colspan);
            $cell->setValue(__('No entries found. '));
            $cell->appendChild(Widget::Anchor(__('Create an entry.'), $url . '/new'));
            $row->appendChild($cell);
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