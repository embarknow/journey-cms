<?php

namespace Embark\CMS\Actors\Schemas;

use Embark\CMS\Database\Exception as DatabaseException;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Metadata\Types\Pagination;
use Embark\CMS\Metadata\Types\QueryOptions;
use Embark\CMS\Metadata\Types\Sorting;
use Embark\CMS\Schemas\Controller as SchemaController;
use Context;
use Entry;
use Field;
use PDO;
use PDOStatement;
use Profiler;
use Section;
use Symphony;
use XMLDocument;

class DatasourceQuery
{
    protected $filterQueries;
    protected $limitStart;
    protected $limitLength;
    protected $sortQueries;

    public function __construct()
    {
        $this->filterQueries = [];
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
        $statement = Symphony::Database()->prepare($this);

        // Build Entry Records
        if ($statement->execute()) {
            return $statement;
        }

        return false;
    }

    public function filterBySubQuery($query)
    {
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
