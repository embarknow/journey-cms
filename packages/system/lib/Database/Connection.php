<?php

namespace Embark\CMS\Database;

use Exception;
use Profiler;
use PDO;

class Connection
{
    const UPDATE_ON_DUPLICATE = 1;

    protected $log;
    protected $conf;
    protected $queryCaching;
    protected $lastException;
    protected $lastQuery;
    protected $string;

    public function __construct($conf)
    {
        $this->conf = $conf;
        $this->queryCaching = true;
        $this->string = sprintf(
            'mysql:host=%s;port=%s;dbname=%s',
            $conf->host,
            $conf->port,
            $conf->database
        );
    }

    public function beginTransaction()
    {
        return $this->connection->beginTransaction();
    }

    public function commit()
    {
        return $this->connection->commit();
    }

    public function rollBack()
    {
        return $this->connection->rollBack();
    }

    public function connect()
    {
        // Already connected:
        if (isset($this->connection)) {
            return true;
        }

        // Establish new connection:
        $this->connection = new PDO($this->string, $this->conf->user, $this->conf->password, [
            PDO::ATTR_ERRMODE =>                    PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE =>         PDO::FETCH_OBJ,
            PDO::ATTR_PERSISTENT =>                 false,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY =>   true,
            PDO::ATTR_EMULATE_PREPARES =>           false
        ]);

        $this->execute('SET NAMES utf8');
        $this->execute('SET time_zone = "+00:00"');
        $this->execute('SET storage_engine = "InnoDB"');

        return true;
    }

    public function connected()
    {
        return $this->connection instanceof PDO;
    }

    public function execute($statement)
    {
        return $this->connection->exec($statement);
    }

    public function prepare($statement, array $driver_options = [])
    {
        return $this->connection->prepare($statement, $driver_options);
    }

    public function createDataTableName($schema, $field, $guid)
    {
        $table = $schema . '_' . $field;

        if (strlen($table) > 64) {
            $table = trim(substr($table, 0, 63 - strlen($guid)), '-_');
            $table .= '_' . $guid;
        }

        return $table;
    }

    public function prepareQuery($query, array $values = null)
    {
        if (is_array($values) && empty($values) === false) {
            // Sanitise values:
            $values = array_map([$this, 'escape'], $values);

            // Inject values:
            $query = vsprintf(trim($query), $values);
        }

        if (isset($this->queryCaching)) {
            $query = preg_replace('/^SELECT\s+/i', 'SELECT SQL_'.(!$this->queryCaching ? 'NO_' : NULL).'CACHE ', $query);
        }

        return $query;
    }

    public function close()
    {
        $this->connection = null;
    }

    public function escape($string)
    {
        return substr($this->connection->quote($string), 1, -1);
    }

    public function insert($table, array $fields, $flag = null)
    {
        $values = [];
        $sets   = [];

        foreach ($fields as $key => $value) {
            if (strlen($value) == 0) {
                $sets[] = "`{$key}` = NULL";
            }

            else {
                $values[] = $value;
                $sets[] = "`{$key}` = '%" . count($values) . '$s\'';
            }
        }

        $query = "INSERT INTO `{$table}` SET " . implode(', ', $sets);

        if ($flag == static::UPDATE_ON_DUPLICATE) {
            $query .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $sets);
        }

        $this->query($query, $values);

        return $this->connection->lastInsertId();
    }

    public function update($table, array $fields, array $values = null, $where = null)
    {
        $set_values = [];
        $sets       = [];

        foreach ($fields as $key => $value) {
            if (strlen($value) == 0) {
                $sets[] = "`{$key}` = NULL";
            }

            else {
                $set_values[] = $value;
                $sets[] = "`{$key}` = '%s'";
            }
        }

        if (!is_null($where)) {
            $where = " WHERE {$where}";
        }

        $values = (is_array($values) && !empty($values)
            ? array_merge($set_values, $values)
            : $set_values
        );

        $this->query("UPDATE `{$table}` SET " . implode(', ', $sets) . $where, $values);
    }

    public function delete($table, array $values = null, $where = null)
    {
        return $this->query("DELETE FROM `$table` WHERE {$where}", $values);
    }

    public function truncate($table)
    {
        return $this->query("TRUNCATE TABLE `{$table}`");
    }

    public function query($query, array $values = null, $return_type = null, $buffer = null)
    {
        if (is_null($return_type)) {
            $return_type = __NAMESPACE__ . '\\ResultIterator';
        }

        if (!is_null($buffer)) {
            throw new Exception('buffer argument is deprecated.');
        }

        $query = $this->prepareQuery($query, $values);
        $this->lastException = null;
        $this->lastQuery = $query;

        try {
            Profiler::begin('Executing database query');
            Profiler::store('query', $query, 'system/database-query action/executed data/sql text/sql');
            $result = $this->connection->query($query);
            Profiler::end();
        } catch (PDOException $e) {
            Profiler::store('exception', $e->getMessage(), 'system/exeption');
            Profiler::end();

            $this->lastException = $e;

            $this->log['error'][] = [
                'query' =>      $query,
                'message' =>    $e->getMessage(),
                'code' =>       $e->getCode()
            ];

            throw new Exception(
                __(
                    'MySQL Error (%1$s): %2$s in query "%3$s"',
                    [$e->getCode(), $e->getMessage(), $query]
                ),
                end($this->log['error'])
            );
        }

        return new $return_type($result);
    }

    public function cleanFields(array $array)
    {
        foreach ($array as $key => $val) {
            $array[$key] = (strlen($val) == 0 ? 'NULL' : "'".$this->escape(trim($val))."'");
        }

        return $array;
    }

    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }

    public function lastError()
    {
        return [
            $this->lastException->getCode(),
            $this->lastException->getMessage(),
            $this->lastQuery
        ];
    }

    public function lastQuery()
    {
        return $this->lastQuery;
    }
}
