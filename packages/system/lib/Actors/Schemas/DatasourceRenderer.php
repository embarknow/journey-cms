<?php

namespace Embark\CMS\Actors\Schemas;

use Embark\CMS\Database\Exception as DatabaseException;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Metadata\Types\Pagination;
use Embark\CMS\Metadata\Types\QueryOptions;
use Embark\CMS\Metadata\Types\Sorting;
use Context;
use Entry;
use Field;
use PDO;
use Profiler;
use Section;
use Symphony;
use XMLDocument;

class DatasourceRenderer
{
    protected $datasource;
    protected $schema;

    public function __construct(Datasource $datasource, SchemaInterface $schema)
    {
        $this->datasource = $datasource;
        $this->schema = $schema;
    }

    public function canExecute()
    {
        return true;
    }

    public function createQuery()
    {
        Profiler::begin('Creating query');

        $query = new DatasourceQuery();

        $this->datasource['sorting']->appendQuery($query, $this->schema);
        $this->datasource['pagination']->appendQuery($query, $this->schema);

        // $output = Controller::toXML($this);
        // $output->formatOutput = true;

        // echo '<pre>', htmlentities($output->saveXML($output->documentElement)), '</pre>';

        // // Process Datasource Filters for each of the Fields
        // if (isset($this->datasource['filters'])) {
        //  foreach ($this->datasource['filters'] as $k => $filter) {
        //      if ($filter['element-name'] == 'system:id') {
        //          $filter_value = $this->prepareFilterValue($filter['value'], new Context());

        //          if (!is_array($filter_value)) continue;

        //          $filter_value = array_map('intval', $filter_value);

        //          if (empty($filter_value)) continue;

        //          $where[] = sprintf(
        //              "(e.id %s IN (%s))",
        //              ($filter['type'] == 'is-not' ? 'NOT' : null),
        //              implode(',', $filter_value)
        //          );
        //      }

        //      else {
        //          $field = $schema->fetchFieldByHandle($filter['element-name']);

        //          if ($field instanceof Field) {
        //              $field->buildFilterQuery($filter, $joins, $where, new Context());
        //          }
        //      }
        //  }
        // }

        Profiler::end();

        return $query;
    }

    public function execute()
    {
        $result = new XMLDocument();
        $result->appendChild($result->createElement($this->datasource['resource']['handle']));
        $root = $result->documentElement;
        $root->setAttribute('schema', $this->schema['resource']['handle']);

        $pagination = $this->datasource['pagination'];
        $query = $this->createQuery($this->schema);

        try {
            Profiler::begin('Executing query');

            $statement = Symphony::Database()->prepare($query);
            $valid = $statement->execute();

            Profiler::end();

            // Build Entry Records
            if ($valid) {
                Profiler::begin('Creating entry elements');

                $statement->bindColumn('entry_id', $entryId, PDO::PARAM_INT);

                while ($row = $statement->fetch(PDO::FETCH_BOUND)) {
                    $entry = Entry::loadFromId($entryId);

                    $wrapper = $result->createElement('entry');
                    $wrapper->setAttribute('id', $entry->entry_id);
                    $root->appendChild($wrapper);

                    // If there are included elements, need an entry element.
                    if ($this->datasource['elements'] instanceof DatasourceOutputElements) {
                        $this->datasource['elements']->appendElements($wrapper, $this->datasource, $this->schema, $entry);
                    }
                }

                Profiler::end();
            }

            if (isset($pagination) && $pagination['append']) {
                Profiler::begin('Creating pagination element');

                $statement = Symphony::Database()->prepare("
                    select found_rows() as `total`
                ");
                $statement->execute();
                $statement->bindColumn('total', $total, PDO::PARAM_INT);
                $statement->fetchAll();

                $pagination->setTotal($total);

                $root->appendChild($pagination->createElement($result));

                Profiler::store('total-entries', $pagination['total-entries']);
                Profiler::store('entries-per-page', $pagination['entries-per-page']);
                Profiler::store('current-page', $pagination['current-page']);
                Profiler::end();
            }
        }

        catch (DatabaseException $e) {
            $root->appendChild($result->createElement(
                'error', $e->getMessage()
            ));
        }

        return $result;
    }
}
