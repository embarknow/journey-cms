<?php

namespace Embark\CMS\Schemas;

use Embark\CMS\Database\Connection;

class SchemaSelectQuery
{
    protected $database;
    protected $filterQueries;
    protected $parameters;
    protected $limitStart;
    protected $limitLength;
    protected $sortQueries;

    public function __construct(Connection $database)
    {
        $this->database = $database;
        $this->filterQueries = [];
        $this->parameters = [];
        $this->sortQueries = [];
    }

    public function __toString()
    {
        $query = "select distinct\n\tentries.entry_id\nfrom\n\tentries";
        $where = $order = [];

        if (!empty($this->filterQueries)) {
            foreach ($this->filterQueries as $index => $item) {
                switch ($item->type) {
                    case 'subquery':
                        $query .= "\nright join\n\t({$item->query})\n\tas filter{$index} using (entry_id)";
                        $where[] = "filter{$index}.entry_id is not null";
                        break;
                }
            }
        }

        if (!empty($this->sortQueries)) {
            foreach ($this->sortQueries as $index => $item) {
                switch ($item->type) {
                    case 'metadata':
                        $order[] = "entries.{$item->column} {$item->direction}";
                        break;

                    case 'random':
                        $order[] = 'rand()';
                        break;

                    case 'subquery':
                        $query .= "\nright join\n\t({$item->query})\n\tas order{$index} using (entry_id)";
                        $order[] = "order{$index}.order_id {$item->direction}";
                        break;
                }
            }
        }

        if (false === empty($where)) {
            $query .= "\nwhere\n\t" . implode("\n\tand ", $where);
        }

        if (false === empty($order)) {
            $query .= "\norder by\n\t" . implode(",\n\t", $order);
        }

        else {
            $query .= "\norder by\n\tentries.entry_id";
        }

        if (isset($this->limitStart, $this->limitLength)) {
            $query .= "\nlimit\n\t{$this->limitStart}, {$this->limitLength}";
        }

        return $query;
    }

    public function execute()
    {
        $statement = $this->database->prepare($this);

        foreach ($this->parameters as $key => $value) {
            $statement->bindValue($key, $value);
        }

        // Build Entry Records
        if ($statement->execute()) {
            return $statement;
        }

        return false;
    }

    public function filterBySubQuery($query, array $parameters = [])
    {
        $id = 'filter' . count($this->filterQueries);

        if (false === empty($parameters)) {
            $keys = array_keys($parameters);

            foreach ($keys as &$key) {
                $key = ':' . $id . substr($key, 1);
            }

            $query = str_replace(':', ':' . $id, $query);
            $parameters = array_combine($keys, $parameters);
            $this->parameters = array_merge($this->parameters, $parameters);
        }

        $this->filterQueries[] = (object)[
            'type' =>       'subquery',
            'query' =>      $query
        ];
    }

    public function sortByMetadata($column, $direction)
    {
        $this->sortQueries[] = (object)[
            'type' =>       'metadata',
            'column' =>     $column,
            'direction' =>  $direction
        ];
    }

    public function sortRandomly()
    {
        $this->sortQueries[] = (object)[
            'type' =>       'random'
        ];
    }

    public function sortBySubQuery($query, $direction)
    {
        $this->sortQueries[] = (object)[
            'type' =>       'subquery',
            'query' =>      $query,
            'direction' =>  $direction
        ];
    }

    public function limitTo($start, $length)
    {
        $this->limitStart = $start;
        $this->limitLength = $length;
    }
}
