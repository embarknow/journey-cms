<?php

namespace Embark\CMS\Actors\Section;

use Embark\CMS\Database\Exception as DatabaseException;
use Embark\CMS\Structures\Pagination;
use Embark\CMS\Structures\QueryOptions;
use Embark\CMS\Structures\Sorting;
use Context;
use Field;
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

    public function execute(Context $parameter_output, $joins = null, array $where = [], $filter_operation_type = 1)
    {
        $execute = true;

        Profiler::begin('Creating query');

        $result = new XMLDocument();
        $result->appendChild($result->createElement($this->datasource['resource']['handle']));
        $root = $result->documentElement;

        // Grab the section
        $section = Section::loadFromHandle($this->datasource['section']);
        $root->setAttribute('section', $section->handle);

        if ($this->datasource['pagination'] instanceof Pagination) {
            $pagination = $this->datasource['pagination']->replaceParameters($parameter_output);
        }

        // Apply sorting:
        $order = 'e.id ASC';

        if ($this->datasource['sorting'] instanceof Sorting) {
            $sorting = $this->datasource['sorting']->replaceParameters($parameter_output);

            // Sort randomly:
            if (Sorting::RANDOM === $soring['direction']) {
                $order = 'rand()';
            }

            // Sort ascending or descending:
            else {
                $column = 'e.id';

                // System field:
                if (preg_match('/^system:/i', $sorting['field'])) {
                    switch (preg_replace('/^system:/i', null, $sorting['field'])) {
                        case 'id':
                            $column = 'e.id';
                            break;

                        case 'creation-date':
                            $column = 'e.creation_date';
                            break;

                        case 'modification-date':
                            $column = 'e.modification_date';
                            break;
                    }
                }

                // Section field:
                else {
                    $field = $section->fetchFieldByHandle($sorting['field']);
                    $join = null;

                    if (
                        $field instanceof Field
                        && $field->isSortable()
                        && method_exists($field, 'buildSortingQuery')
                    ) {
                        $field->buildSortingQuery($join, $order);

                        $joins .= sprintf($join, $field->section, $field->{'element-name'});
                        $order = sprintf($order, $sorting['direction']);
                    }
                }
            }
        }

        // $output = Controller::toXML($this);
        // $output->formatOutput = true;

        // echo '<pre>', htmlentities($output->saveXML($output->documentElement)), '</pre>';
        // var_dump($this); exit;

        // // Process Datasource Filters for each of the Fields
        // if (isset($this->datasource['filters'])) {
        //  foreach ($this->datasource['filters'] as $k => $filter) {
        //      if ($filter['element-name'] == 'system:id') {
        //          $filter_value = $this->prepareFilterValue($filter['value'], $parameter_output);

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
        //          $field = $section->fetchFieldByHandle($filter['element-name']);

        //          if ($field instanceof Field) {
        //              $field->buildFilterQuery($filter, $joins, $where, $parameter_output);
        //          }
        //      }
        //  }
        // }

        // Escape percent symbol:
        $where = array_map(function($string) {
            return str_replace('%', '%%', $string);
        }, $where);

        // Unoptimized select statement:
        $select_keywords = array(
            'SELECT', 'DISTINCT', 'SQL_CALC_FOUND_ROWS'
        );

        // Remove distinct keyword:
        if ($this->datasource['query'] instanceof QueryOptions && false === $this->datasource['query']['distinct-select']) {
            $select_keywords = array_diff(
                $select_keywords, array('DISTINCT')
            );
        }

        $o_where = $where;
        $o_joins = $joins;
        $o_order = $order;
        $query = sprintf('
            %1$s e.id, e.section, e.user_id, e.creation_date, e.modification_date
            FROM `entries` AS `e`
            %2$s
            WHERE `section` = "%3$s"
            %4$s
            ORDER BY %5$s
            LIMIT %6$d, %7$d',

            implode($select_keywords, ' '),
            $o_joins,
            $section->handle,
            is_array($o_where) && !empty($o_where) ? 'AND (' . implode(($filter_operation_type == 1 ? ' AND ' : ' OR '), $o_where) . ')' : null,
            $o_order,
            $pagination['record-start'],
            $pagination['entries-per-page']
        );

        // Replace duplicate right join statements:
        if ($this->datasource['query'] instanceof QueryOptions && $this->datasource['query']['reduce-right-joins']) {
            $joins = array(); $changes = array();
            $replace = function ($matches) use (&$joins, &$changes) {
                if (isset($joins[$matches[1]])) {
                    $key = '`' . $matches[2] . '`';
                    $changes[$key] = $joins[$matches[1]];
                } else {
                    $joins[$matches[1]] = '`' . $matches[2] . '`';

                    Profiler::end();

                    return $matches[0];
                }
            };

            // Find and replace duplicate join statements:
            $query = preg_replace_callback(
                '%RIGHT JOIN `(.+?)` AS `(.+?)` ON \(e\.id = `.+?`\.entry_id\)%',
                $replace, $query
            );

            // Replace old table aliases:
            $query = str_replace(
                array_keys($changes), $changes, $query
            );
        }

        Profiler::end();

        try {
            Profiler::begin('Executing query');

            $entries = Symphony::Database()->query($query, [
                    $section->handle,
                    $section->{'publish-order-handle'}
                ], __NAMESPACE__ . '\\DatasourceResultIterator'
            );

            Profiler::end();

            // Build Entry Records
            if ($entries->valid()) {
                Profiler::begin('Creating entry data');

                $parameters = [];
                $schema = [];
                $ids = [];
                $data = [];

                foreach ($entries as $entry) {
                    $ids[] = $entry->id;
                }

                // Figure out what fields to fetch, so we don't have to fetch them all:
                if ($this->datasource['elements'] instanceof DatasourceOutputElements) {
                    $this->datasource['elements']->appendSchema($schema, $section);
                }

                if ($this->datasource['parameters'] instanceof DatasourceOutputParameters) {
                    $this->datasource['parameters']->appendSchema($schema, $section);
                }

                // Load entry data:
                foreach ($schema as $field => $instance) {
                    $data[$field] = $instance->loadDataFromDatabaseEntries($section->handle, $ids);
                }

                $entries->setSchema($schema);
                $entries->setData($data);

                Profiler::end();

                Profiler::begin('Creating entry parameters');

                // Add in the param output values to the parameter_output object
                foreach ($parameters as $name => $values) {
                    $values = array_filter($values);

                    if (empty($values)) {
                        continue;
                    }

                    $parameter_output->{$name} = array_unique($values);
                }

                Profiler::end();

                Profiler::begin('Creating entry elements');

                $parameters = [];

                foreach ($entries as $entry) {
                    $wrapper = $result->createElement('entry');
                    $wrapper->setAttribute('id', $entry->id);
                    $root->appendChild($wrapper);

                    // If there are included elements, need an entry element.
                    if ($this->datasource['elements'] instanceof DatasourceOutputElements) {
                        $this->datasource['elements']->appendElements($wrapper, $this->datasource, $section, $entry);
                    }

                    if ($this->datasource['parameters'] instanceof DatasourceOutputParameters) {
                        $this->datasource['parameters']->appendParameters($parameters, $this->datasource, $section, $entry);
                    }
                }

                Profiler::end();
            }

            if (isset($pagination) && $pagination['append']) {
                Profiler::begin('Creating pagination element');

                $pagination->setTotal(Symphony::Database()->query("SELECT FOUND_ROWS() AS `total`")->current()->total);

                $root->appendChild($pagination->createElement($result));

                Profiler::store('total-entries', $pagination['total-entries']);
                Profiler::store('entries-per-page', $pagination['entries-per-page']);
                Profiler::store('current-page', $pagination['current-page']);
                Profiler::end();
            }

            if (isset($sorting) && $sorting['append']) {
                Profiler::begin('Creating sorting element');

                $root->appendChild($sorting->createElement($result));

                Profiler::store('field', $sorting['field']);
                Profiler::store('direction', $sorting['direction']);
                Profiler::end();
            }
        } catch (DatabaseException $e) {
            $root->appendChild($result->createElement(
                'error', $e->getMessage()
            ));
        }

        return $result;
    }
}
