<?php

namespace Embark\CMS\Views\Section;

use Embark\CMS\Actors\Schemas\DatasourceQuery;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldColumnInterface;
use Embark\CMS\Fields\FieldPreviewInterface;
use Embark\CMS\Fields\FieldPreviewCaptionInterface;
use Embark\CMS\Fields\FieldPreviewFigureInterface;
use Embark\CMS\Link;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Schemas\SchemaInterface;
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

    public function appendFooterTo(HTMLDocument $page, SectionView $view, SchemaInterface $schema, Link $pageLink)
    {
        // Delete entries and then redirect:
        if (isset($_POST['entries'], $_POST['action']['delete'])) {
            try {
                $schema->deleteEntries($_POST['entries']);

                redirect($pageLink);
            }

            // Something went wrong, do not commit:
            catch (\Exception $error) {
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

    public function appendHeaderTo(HTMLDocument $page, SectionView $view, SchemaInterface $schema, Link $pageLink)
    {
        $page->setTitle(__('%1$s &ndash; %2$s', [
            __('Symphony'), $view['name']
        ]));
        $page->appendBreadcrumb(Widget::Anchor($view['name'], $pageLink));

        $page->appendButton(Widget::Anchor(
            __('Create New'),
            $pageLink . '/new',
            [
                'title' => __('Create a new entry'),
                'class' => 'create button'
            ]
        ));

        if (false === isset($_GET['cards'])) {
            $page->appendButton(Widget::Anchor(
                __('Cards View'),
                $pageLink . '?cards',
                [
                    'title' => __('Switch to cards view'),
                    'class' => 'button'
                ]
            ));
        }

        else {
            $page->appendButton(Widget::Anchor(
                __('Table View'),
                $pageLink,
                [
                    'title' => __('Switch to table view'),
                    'class' => 'button'
                ]
            ));
        }

        $page->Form->setAttribute('enctype', 'multipart/form-data');
    }

    protected function appendResultsTo(DOMElement $table, SectionView $view, SchemaInterface $schema, Link $sortLink, Link $pageLink, $statement)
    {
        $document = $table->ownerDocument;

        if (isset($view['title'])) {
            $titleField = $view['title']->resolveInstanceOf(FieldPreviewInterface::class);
        }

        if (isset($view['figure'])) {
            $figureField = $view['figure']->resolveInstanceOf(FieldPreviewFigureInterface::class);
        }

        if (isset($view['caption'])) {
            $captionField = $view['caption']->resolveInstanceOf(FieldPreviewCaptionInterface::class);
        }

        $statement->bindColumn('entry_id', $entryId, PDO::PARAM_INT);

        while ($statement->fetch(PDO::FETCH_BOUND)) {
            $entry = Entry::loadFromId($entryId);
            $article = $document->createElement('article');
            $table->appendChild($article);

            if (isset($titleField)) {
                $title = $document->createElement('h1');
                $article->appendChild($title);
                $titleField->appendTitleTo($title, $schema, $entry, $pageLink);
            }

            if (isset($figureField) || isset($captionField)) {
                $preview = $document->createElement('figure');
                $article->appendChild($preview);

                if (isset($figureField)) {
                    $figureField->appendFigureTo($preview, $schema, $entry, $pageLink);
                }

                if (isset($captionField)) {
                    $captionField->appendCaptionTo($preview, $schema, $entry, $pageLink);
                }
            }

            foreach ($this->findAllColumns() as $column) {
                $list = $document->createElement('dl');
                $article->appendChild($list);

                $column->appendHeaderTo($list, $schema, $entry, $sortLink);
                $column->appendBodyTo($list, $schema, $entry, $pageLink);
            }

            $input = Widget::Input('entries[]', $entry->entry_id, 'checkbox');
            $article->prependChild($input);
        }
    }

    protected function appendSortingQueries($handle, $query, SchemaInterface $schema, Link &$link)
    {
        // Prime with parameters:
        $link = $link->withParameters($_GET);

        // Change the stored sorting information:
        if (isset($_GET['sort'], $_GET['direction'])) {
            $this->saveSorting($handle, $_GET['sort'], $_GET['direction']);

            $link = $link
                ->withoutParameter('sort')
                ->withoutParameter('direction');

            redirect($link);
        }

        // Sort based on the stored sorting information:
        else if ($this->hasSorting($handle)) {
            $this->applySorting($handle, $query, $schema);
        }

        // Sort entries by the first column:
        else {
            $column = $this->findFirstColumn();
            $column->appendSortingQuery($query, $schema);

            $this->saveSorting(
                $handle,
                $column['name'],
                $column['sorting']['direction']
            );
        }

        // Add sorting parameters to our link:
        $link = $link
            ->withParameter('sort', $_SESSION["{$handle}.sort"])
            ->withParameter('direction', $_SESSION["{$handle}.direction"]);
    }

    public function appendListTo(HTMLDocument $page, SectionView $view, SchemaInterface $schema, Link $pageLink)
    {
        $handle = $view['resource']['handle'];
        $guid = $schema->getGuid();
        $query = new DatasourceQuery();
        $sortLink = clone $pageLink;

        // Show only entries from this schema:
        $query->filterBySubQuery("select entry_id from entries where schema_id = '{$guid}'");

        // Sort the results:
        $this->appendSortingQueries($handle, $query, $schema, $sortLink);

        // Build table header:
        $table = $page->createElement('section');
        $table->addClass('entries');
        $page->Form->appendChild($table);

        if (false === isset($_GET['cards'])) {
            $table->addClass('table');
        }

        else {
            $table->addClass('cards');
        }

        if ($statement = $query->execute() and $statement->rowCount()) {
            $this->appendResultsTo($table, $view, $schema, $sortLink, $pageLink, $statement);
        }

        else {
            $error = $page->createElement('p');
            $error->addClass('error missing');
            $error->setValue(__('No entries found. '));
            $error->appendChild(Widget::Anchor(__('Create an entry.'), $pageLink . '/new'));
            $table->appendChild($error);
        }
    }

    /**
     * Apply the stored sorting direction.
     *
     * @param   string          $key
     *  The section handle or unique key.
     * @param   object          $query
     *  The schema query to apply sorting to.
     * @param   SchemaInterface $schema
     *  The schema being sorted on.
     */
    protected function applySorting($key, $query, SchemaInterface $schema)
    {
        $column = $this->findColumnByName($_SESSION["{$key}.sort"]);
        $column->appendSortingQuery($query, $schema, $_SESSION["{$key}.direction"]);
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

    /**
     * Check to see if the session has a sorting direction for a specific key.
     *
     * @param   string  $key
     *  The section handle or unique key.
     *
     * @return  boolean
     */
    protected function hasSorting($key)
    {
        return isset($_SESSION["{$key}.sort"], $_SESSION["{$key}.direction"]);
    }

    /**
     * Store the sorting direction for a specific key in the session.
     *
     * @param   string  $key
     *  The section handle or unique key.
     * @param   string  $column
     *  The name of the column being sorted.
     * @param   string  $direction
     *  The direction to sort in (`asc` or `desc`).
     */
    protected function saveSorting($key, $column, $direction)
    {
        $_SESSION["{$key}.sort"] = $column;
        $_SESSION["{$key}.direction"] = $direction;
    }
}
