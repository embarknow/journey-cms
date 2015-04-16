<?php

namespace Embark\CMS\Views\Section;

use Embark\CMS\Configuration\Controller as Configuration;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldColumnInterface;
use Embark\CMS\Fields\FieldPreviewInterface;
use Embark\CMS\Fields\FieldPreviewCaptionInterface;
use Embark\CMS\Fields\FieldPreviewFigureInterface;
use Embark\CMS\Link;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Schemas\SchemaSelectQuery;
use AlertStack;
use DOMElement;
use Entry;
use HTMLDocument;
use PDO;
use Symphony;
use Widget;

class SectionListView implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'columns' => [
                'type' =>   new SectionListColumns()
            ],
            'details' => [
                'type' =>   new SectionListColumns()
            ]
        ]);
    }

    public function appendFooterTo(HTMLDocument $page, SectionView $view, SchemaInterface $schema, Link $sortLink, Link $pageLink, $pagination)
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

        // Display pagination:
        if ($pagination->totalPages > 1) {
            $ul = $page->createElement('ul');
            $ul->setAttribute('class', 'page');

            // First:
            $a = $li = $page->createElement('li');
            $ul->appendChild($li);

            if ($pagination->currentPage > 1) {
                $a = $page->createElement('a');
                $a->setAttribute('href', $sortLink->withParameter('page', '1'));
                $li->appendChild($a);
            }

            $a->setValue(__('⇤ First'));

            // Previous:
            $a = $li = $page->createElement('li');
            $ul->appendChild($li);

            if ($pagination->currentPage > 1) {
                $a = $page->createElement('a');
                $a->setAttribute('href', $sortLink->withParameter('page', (string)($pagination->currentPage - 1)));
                $li->appendChild($a);
            }

            $a->setValue(__('⇠ Previous'));

            // Summary:
            $li = $page->createElement('li', __('Page %1$s of %2$s', [
                $pagination->currentPage,
                $pagination->totalPages
            ]));
            $li->setAttribute('title', __('Viewing %1$s - %2$s of %3$s entries', [
                $pagination->pageStart + 1,
                $pagination->pageEnd,
                $pagination->totalEntries
            ]));
            $ul->appendChild($li);

            // Next:
            $a = $li = $page->createElement('li');
            $ul->appendChild($li);

            if ($pagination->currentPage < $pagination->totalPages) {
                $a = $page->createElement('a');
                $a->setAttribute('href', $sortLink->withParameter('page', (string)($pagination->currentPage + 1)));
                $li->appendChild($a);
            }

            $a->setValue(__('Next ⇢'));

            // Last:
            $a = $li = $page->createElement('li');
            $ul->appendChild($li);

            if ($pagination->currentPage < $pagination->totalPages) {
                $a = $page->createElement('a');
                $a->setAttribute('href', $sortLink->withParameter('page', (string)($pagination->totalPages)));
                $li->appendChild($a);
            }

            $a->setValue(__('Last ⇥'));

            $page->Form->appendChild($ul);
        }
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

        // User can switch between table and cards view:
        if (isset($this['columns'], $this['details'])) {
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
        }

        $page->Form->setAttribute('enctype', 'multipart/form-data');
    }

    protected function appendResultsTo(DOMElement $table, SectionView $view, SchemaInterface $schema, Link $sortLink, Link $pageLink, $statement)
    {
        $document = $table->ownerDocument;

        if (isset($this['columns'])) {
            $columns = $this['columns']->resolveInstanceOf(SectionListColumns::class);
        }

        if (isset($this['details'])) {
            $details = $this['details']->resolveInstanceOf(SectionListColumns::class);
        }

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

            if (isset($titleField) || isset($figureField) || isset($captionField)) {
                $content = $document->createElement('div');
                $content->addClass('preview');
                $article->appendChild($content);

                if (isset($titleField)) {
                    $title = $document->createElement('h1');
                    $content->appendChild($title);
                    $titleField->appendTitleTo($title, $schema, $entry, $pageLink);
                }

                if (isset($figureField) || isset($captionField)) {
                    $preview = $document->createElement('figure');
                    $content->appendChild($preview);

                    if (isset($figureField)) {
                        $figureField->appendFigureTo($preview, $schema, $entry, $pageLink);
                    }

                    if (isset($captionField)) {
                        $caption = $document->createElement('figcaption');
                        $preview->appendChild($caption);

                        $captionField->appendCaptionTo($caption, $schema, $entry, $pageLink);
                    }
                }
            }

            if (isset($columns)) {
                $content = $document->createElement('div');
                $content->addClass('columns');
                $article->appendChild($content);

                foreach ($columns->findAllColumns() as $column) {
                    $list = $document->createElement('dl');
                    $content->appendChild($list);

                    $column->appendHeaderTo($list, $schema, $entry, $sortLink);
                    $column->appendBodyTo($list, $schema, $entry, $pageLink);
                }

                if (isset($details)) {
                    $nav = $document->createElement('nav');
                    $content->appendChild($nav);
                    $info = $document->createElement('a');
                    $info->addClass('info');
                    $info->setValue('ℹ');
                    $nav->appendChild($info);
                }
            }

            if (isset($details)) {
                $content = $document->createElement('div');
                $content->addClass('details');
                $article->appendChild($content);

                foreach ($details->findAllColumns() as $column) {
                    $list = $document->createElement('dl');
                    $content->appendChild($list);

                    $column->appendHeaderTo($list, $schema, $entry, $sortLink);
                    $column->appendBodyTo($list, $schema, $entry, $pageLink);
                }

                if (isset($columns)) {
                    $nav = $document->createElement('nav');
                    $content->appendChild($nav);
                    $close = $document->createElement('a');
                    $close->addClass('close');
                    $close->setValue('✖');
                    $nav->appendChild($close);
                }
            }

            $input = Widget::Input('entries[]', $entry->entry_id, 'checkbox');
            $article->prependChild($input);
        }
    }

    protected function appendSortingQueries($handle, $query, SchemaInterface $schema, Link &$link)
    {
        if (false === isset($this['columns'])) return;

        $columns = $this['columns']->resolveInstanceOf(SectionListColumns::class);

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
            $column = $columns->findFirstColumn();
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
        $query = new SchemaSelectQuery(Symphony::Database());
        $sortLink = clone $pageLink;

        $this->appendHeaderTo($page, $view, $schema, $pageLink);

        // Show only entries from this schema:
        $query->filterBySubQuery("select entry_id from entries where schema_id = '{$guid}'");

        // Filter the results:
        if (isset($view['filters'])) {
            $filters = $view['filters']->resolveInstanceOf(SectionFilters::class);
            $filters->appendFilteringQueries($query, $schema);
        }

        // Sort the results:
        $this->appendSortingQueries($handle, $query, $schema, $sortLink);

        // Calculate pagination:
        $currentPage = (
            isset($_REQUEST['page'])
            && is_numeric($_REQUEST['page'])
                ? max(1, intval($_REQUEST['page']))
                : 1
        );
        $pageSize = Configuration::read('main')['admin']['pagination'];
        $pageStart = ($currentPage - 1) * $pageSize;
        $pageEnd = $pageStart + $pageSize;
        // Todo: Make sure filteres are applied to this count:
        $totalEntries = $schema->countEntries();
        $totalPages = ceil($totalEntries / $pageSize);

        $query->limitTo($pageStart, $pageSize);

        // Build table header:
        $table = $page->createElement('section');
        $table->addClass('entries');
        $page->Form->appendChild($table);

        // User can switch between table and cards view:
        if (isset($this['columns'], $this['details'])) {
            if (false === isset($_GET['cards'])) {
                $table->addClass('table');
            }

            else {
                $table->addClass('cards');
            }
        }

        // Only table view is available:
        else if (isset($this['columns'])) {
            $table->addClass('table');
        }

        // Only cards view is available:
        else if (isset($this['details'])) {
            $table->addClass('cards');
        }

        // Display results:
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

        $this->appendFooterTo($page, $view, $schema, $sortLink, $pageLink, (object)[
            'currentPage' =>    $currentPage,
            'pageSize' =>       $pageSize,
            'pageStart' =>      $pageStart,
            'pageEnd' =>        $pageEnd,
            'totalEntries' =>   $totalEntries,
            'totalPages' =>     $totalPages
        ]);
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
        $columns = $this['columns']->resolveInstanceOf(SectionListColumns::class);
        $column = $columns->findColumnByName($_SESSION["{$key}.sort"]);

        if (false === $column) return;

        $column->appendSortingQuery($query, $schema, $_SESSION["{$key}.direction"]);
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
