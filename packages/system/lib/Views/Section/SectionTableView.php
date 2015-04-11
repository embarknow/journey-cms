<?php

namespace Embark\CMS\Views\Section;

use Embark\CMS\Actors\Schemas\DatasourceQuery;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldColumnInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use AlertStack;
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

    public function appendHeader(HTMLDocument $page, SectionView $view, SchemaInterface $schema)
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

    public function appendFooter(HTMLDocument $page, SectionView $view, SchemaInterface $schema)
    {
        // Delete entries and then redirect:
        if (isset($_POST['entries'], $_POST['action']['delete'])) {
            $url = ADMIN_URL . '/publish/' . $view['resource']['handle'];

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

                $page->alerts()->append(
                    __('An error occurred while deleting the selected entries. <a class="more">Show the error.</a>'),
                    AlertStack::ERROR,
                    $error
                );

                // Todo: Log this exception.
            }
        }

        $actions = $page->createElement('div');
        $actions->addClass('actions with-selectable');
        $page->Form->appendChild($actions);

        $actions->appendChild(
            Widget::Submit(
                'action[delete]', __('Delete'),
                [
                    'class' => 'confirm delete',
                    'title' => __('Delete selected entries'),
                ]
            )
        );
    }

    public function appendTable(HTMLDocument $page, SectionView $view, SchemaInterface $schema)
    {
        $url = ADMIN_URL . '/publish/' . $view['resource']['handle'];
        $handle = $view['resource']['handle'];
        $guid = $schema->getGuid();
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

            $_SESSION["{$handle}.sort"] = $column['name'];
        }

        // Build table header:
        $table = $page->createElement('section');
        $table->addClass('entries table');
        $page->Form->appendChild($table);

        // $head = $page->createElement('thead');
        // $table->appendChild($head);

        // foreach ($this->findAllColumns() as $column) {
        //     $active = false;

        //     if (isset($_SESSION["{$handle}.sort"])) {
        //         $active = $_SESSION["{$handle}.sort"] === $column['name'];
        //     }

        //     $column->appendHeaderElement($head, $active, $url);
        // }

        if ($statement = $query->execute() and $statement->rowCount()) {
            $statement->bindColumn('entry_id', $entryId, PDO::PARAM_INT);

            while ($statement->fetch(PDO::FETCH_BOUND)) {
                $entry = Entry::loadFromId($entryId);
                $article = $page->createElement('article');
                $table->appendChild($article);

                foreach ($this->findAllColumns() as $column) {
                    $list = $page->createElement('dl');
                    $article->appendChild($list);

                    if (isset($_SESSION["{$handle}.sort"])) {
                        $active = $_SESSION["{$handle}.sort"] === $column['name'];
                    }

                    $column->appendHeaderElement($list, $active, $url);
                    $column->appendBodyElement($list, $schema, $entry, $url);
                }

                $input = Widget::Input('entries[]', $entry->entry_id, 'checkbox');
                $article->prependChild($input);
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
    }

    public function findAllColumns()
    {
        return $this->findInstancesOf(FieldColumnInterface::class);
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
