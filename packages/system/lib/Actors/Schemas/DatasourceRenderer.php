<?php

namespace Embark\CMS\Actors\Schemas;

use Embark\CMS\Database\Exception as DatabaseException;
use Embark\CMS\Database\TableAliasIndex;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Structures\Pagination;
use Embark\CMS\Structures\QueryOptions;
use Embark\CMS\Structures\Sorting;
use Embark\CMS\Schemas\Controller as SchemaController;
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

    public function __construct(Datasource $datasource)
    {
        $this->datasource = $datasource;
    }

    public function canExecute()
    {
        return true;
    }

    public function createQuery($schema, $joins = null, array $where = [], $filter_operation_type = 1)
    {
        Profiler::begin('Creating query');

        $sqlJoins = [];
        $sqlSorts = [];

        $tables = new TableAliasIndex();
        $tables['entries'] = 'e';

        $this->datasource['sorting']->buildQuery($schema, $tables, $sqlJoins, $sqlSorts);
        $this->datasource['pagination']->buildQuery($schema, $sqlLimits);

        // $output = Controller::toXML($this);
        // $output->formatOutput = true;

        // echo '<pre>', htmlentities($output->saveXML($output->documentElement)), '</pre>';
        // var_dump($this); exit;

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

        // Escape percent symbol:
        $where = array_map(function($string) {
            return str_replace('%', '%%', $string);
        }, $where);

        // Unoptimized select statement:
        $selectKeywords = [
            'select', 'distinct', 'sql_calc_found_rows'
        ];

        // Remove distinct keyword:
        if ($this->datasource['query'] instanceof QueryOptions && false === $this->datasource['query']['distinct-select']) {
            $selectKeywords = array_diff(
                $selectKeywords, array('distinct')
            );
        }

        $o_where = $where;
        $query = sprintf(
            "%s\n\t%s.id\nfrom\n\t`entries` as `%s`%s\nwhere\n\t`schema` = '%s'%s\norder by%s\nlimit %s",
            implode(' ', $selectKeywords),
            $tables['entries'],
            $tables['entries'],
            (
                empty($sqlJoins)
                    ? null
                    : "\n" . implode("\n", $sqlJoins)
            ),
            $schema['resource']['handle'],
            (
                is_array($o_where) && !empty($o_where)
                    ? "\nand (" . implode(($filter_operation_type == 1 ? ' and ' : ' or '), $o_where) . ')'
                    : null
            ),
            (
                empty($sqlSorts)
                    ? null
                    : "\n\t" . implode(",\n\t", $sqlSorts)
            ),
            $sqlLimits
        );

        Profiler::end();

        return $query;
    }

    public function execute(Context $parameter_output, $joins = null, array $where = [], $filter_operation_type = 1)
    {
        $result = new XMLDocument();
        $result->appendChild($result->createElement($this->datasource['resource']['handle']));
        $root = $result->documentElement;

        $schema = SchemaController::read($this->datasource['schema']);
        $root->setAttribute('schema', $schema['resource']['handle']);

        $pagination = $this->datasource['pagination'];
        $query = $this->createQuery($schema, $joins, $where, $filter_operation_type);

        try {
            Profiler::begin('Executing query');

            $statement = Symphony::Database()->prepare($query, [
                $schema['resource']['handle'],
                $schema->{'publish-order-handle'}
            ]);
            $valid = $statement->execute();

            Profiler::end();

            // Build Entry Records
            if ($valid) {
                Profiler::begin('Creating entry elements');

                $statement->bindColumn('id', $entryId, PDO::PARAM_INT);

                while ($row = $statement->fetch(PDO::FETCH_BOUND)) {
                    $entry = Entry::loadFromId($entryId);

                    $wrapper = $result->createElement('entry');
                    $wrapper->setAttribute('id', $entry->id);
                    $root->appendChild($wrapper);

                    // If there are included elements, need an entry element.
                    if ($this->datasource['elements'] instanceof DatasourceOutputElements) {
                        $this->datasource['elements']->appendElements($wrapper, $this->datasource, $schema, $entry);
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
