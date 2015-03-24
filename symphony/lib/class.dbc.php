<?php

	require_once('class.errorhandler.php');

	class DatabaseException extends Exception {
		private $error;

		public function __construct($message, array $error = null) {
			parent::__construct($message);
			$this->error = $error;
		}

		public function getQuery(){
			return (
				isset($this->error['query'])
					? $this->error['query']
					: null
			);
		}

		public function getDatabaseErrorMessage() {
			return (
				isset($this->error['message'])
					? $this->error['message']
					: $this->getMessage()
			);
		}

		public function getDatabaseErrorCode() {
			return (
				isset($this->error['code'])
					? $this->error['code']
					: null
			);
		}
	}

	class DatabaseExceptionHandler extends GenericExceptionHandler{
		public static function render($e) {
			require_once('class.xslproc.php');

			$xml = new DOMDocument('1.0', 'utf-8');
			$xml->formatOutput = true;

			$root = $xml->createElement('data');
			$xml->appendChild($root);

			$details = $xml->createElement('details');
			$details->appendChild($xml->createElement('message', General::sanitize($e->getDatabaseErrorMessage())));
			if(!is_null($e->getQuery())){
				$details->appendChild($xml->createElement('query', General::sanitize($e->getQuery())));
			}
			$root->appendChild($details);

			$trace = $xml->createElement('backtrace');

			foreach($e->getTrace() as $t){

				$item = $xml->createElement('item');

				if(isset($t['file'])) $item->setAttribute('file', General::sanitize($t['file']));
				if(isset($t['line'])) $item->setAttribute('line', $t['line']);
				if(isset($t['class'])) $item->setAttribute('class', General::sanitize($t['class']));
				if(isset($t['type'])) $item->setAttribute('type', $t['type']);
				$item->setAttribute('function', General::sanitize($t['function']));

				$trace->appendChild($item);
			}
			$root->appendChild($trace);

			if (is_object(Symphony::Database()) && method_exists(Symphony::Database(), 'log')) {
				$query_log = Symphony::Database()->log();

				if (count($query_log) > 0) {
					$queries = $xml->createElement('query-log');

					$query_log = array_reverse($query_log);

					foreach($query_log as $q){

						$item = $xml->createElement('item', General::sanitize(trim($q->query)));
						if(isset($q->time)) $item->setAttribute('time', number_format($q->time, 5));
						$queries->appendChild($item);
					}

					$root->appendChild($queries);
				}
			}

			return parent::__transform($xml, 'exception.database.xsl');
		}
	}

	abstract Class Database {
		const UPDATE_ON_DUPLICATE = 1;

		protected $connection;

		abstract public function close();
		abstract public function escape($string);
		abstract public function connect();
		abstract public function insert($table, array $fields, $flag = null);
		abstract public function update($table, array $fields, array $values=NULL, $where = null);
		abstract public function delete($table, array $values=NULL, $where = null);
		abstract public function query($query);
		abstract public function truncate($table);
		abstract public function connected();
	}

	abstract Class DatabaseResultIterator implements Iterator {
		const RESULT_ARRAY = 0;
		const RESULT_OBJECT = 1;

		protected $_result;
		protected $_position;
		protected $_lastPosition;
		protected $_length;
		protected $_current;

		public $resultOutput;

		public function __construct($result) {
			$this->_result = $result;
			$this->_position = 0;
			$this->_lastPosition = null;
			$this->_current = null;
		}

		public function next() {
			$this->_position++;
		}

		public function offset($offset) {
			$this->_position = $offset;
		}

		public function position() {
			return $this->_position;
		}

		public function rewind() {
			$this->_position = 0;
		}

		public function key() {
			return $this->_position;
		}

		public function length() {
			return $this->_length;
		}

		public function valid() {
			return $this->_position < $this->_length;
		}

		public function resultColumn($column) {
			$this->rewind();

			if ($this->valid() === false) return false;

			$result = array();
			$this->resultOutput = DatabaseResultIterator::RESULT_OBJECT;

			foreach ($this as $r) {
				$result[] = $r->$column;
			}

			$this->rewind();

			return $result;
		}

		public function resultValue($key, $offset = 0) {
			if ($offset == 0) {
				$this->rewind();
			}

			else {
				$this->offset($offset);
			}

			if ($this->valid() === false) return false;

			$this->resultOutput = DatabaseResultIterator::RESULT_OBJECT;

			return $this->current()->$key;
		}
	}

	class DBCMySQLResult extends DatabaseResultIterator {
		public function __construct(PDOStatement $result) {
			parent::__construct($result);

			$this->_length = (integer)$result->rowCount();

			$this->resultOutput = self::RESULT_OBJECT;
		}

		public function current() {
			if ($this->_length == 0) {
				throw new DatabaseException('Cannot get current, no data returned.');
			}

			$this->_current = (
				$this->resultOutput == self::RESULT_OBJECT
					? $this->_result->fetch(PDO::FETCH_OBJ, PDO::FETCH_ORI_ABS, $this->position())
					: $this->_result->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_ABS, $this->position())
			);

			return $this->_current;
		}

		public function rewind() {
			if ($this->_length == 0) {
				throw new DatabaseException('Cannot rewind, no data returned.');
			}

			$this->_position = 0;
		}
	}

	class DBCMySQL extends Database {
		protected $log;
		protected $conf;
		protected $queryCaching;
		protected $lastException;
		protected $lastQuery;
		protected $prefix;
		protected $string;

		public function __construct($conf)
		{
			$this->conf = $conf;
			$this->queryCaching = true;
			$this->prefix = $conf->{'table-prefix'};
			$this->string = sprintf(
				'mysql:host=%s;port=%s;dbname=%s',
				$conf->host,
				$conf->port,
				$conf->database
			);
		}

		public function connect() {
			// Already connected:
			if (isset($this->connection)) {
				return true;
			}

			// Establish new connection:
			$this->connection = new PDO($this->string, $this->conf->user, $this->conf->password, array(
				PDO::ATTR_ERRMODE =>					PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE =>			PDO::FETCH_OBJ,
				PDO::ATTR_PERSISTENT =>					false,
				// PDO::MYSQL_ATTR_INIT_COMMAND =>			'SET NAMES utf8; SET time_zone = '+00:00'; SET storage_engine=MYISAM;',
				PDO::MYSQL_ATTR_USE_BUFFERED_QUERY =>	true,
				PDO::ATTR_EMULATE_PREPARES =>			false
			));

			$this->execute('SET NAMES utf8');
			$this->execute('SET time_zone = "+00:00"');
			$this->execute('SET storage_engine = "MYISAM"');

			return true;
		}

		public function connected() {
			return $this->connection instanceof PDO;
		}

		public function execute($statement) {
			return $this->connection->exec($statement);
		}

		public function prepare($statement, array $driver_options = array()) {
			return $this->connection->prepare($statement, $driver_options);
		}

		public function prepareQuery($query, array $values = null) {
			if ($this->prefix != '') {
				$query = preg_replace('/([^\b`]+)/i', $this->prefix . '\\1', $query);
			}

			if (is_array($values) && empty($values) === false) {
				// Sanitise values:
				$values = array_map(array($this, 'escape'), $values);

				// Inject values:
				$query = vsprintf(trim($query), $values);
			}

			if (isset($this->queryCaching)) {
				$query = preg_replace('/^SELECT\s+/i', 'SELECT SQL_'.(!$this->queryCaching ? 'NO_' : NULL).'CACHE ', $query);
			}

			return $query;
		}

		public function close() {
			$this->connection = null;
		}

		public function escape($string) {
			return substr($this->connection->quote($string), 1, -1);
		}

		public function insert($table, array $fields, $flag = null) {
			$values = array(); $sets = array();

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

			if ($flag == Database::UPDATE_ON_DUPLICATE) {
				$query .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $sets);
			}

			$this->query($query, $values, null, false);

			return $this->connection->lastInsertId();
		}

		public function update($table, array $fields, array $values = null, $where = null) {
			$sets = array(); $set_values = array();

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

			$this->query("UPDATE `{$table}` SET " . implode(', ', $sets) . $where, $values, null, false);
		}

		public function delete($table, array $values = null, $where = null) {
			return $this->query("DELETE FROM `$table` WHERE {$where}", $values, null, false);
		}

		public function truncate($table) {
			return $this->query("TRUNCATE TABLE `{$table}`", array(), null, false);
		}

		public function query($query, array $values = null, $return_type = null, $buffer = true) {
			if (!$this->connected()) throw new DatabaseException('No Database Connection Found.');

			if (is_null($return_type)) {
				$return_type = 'DBCMySQLResult';
			}

			$query = $this->prepareQuery($query, $values);
			$this->lastException = null;
			$this->lastQuery = $query;

			try {
				Profiler::begin('Executing database query');
				Profiler::store('query', $query, 'system/database-query action/executed data/sql text/sql');
				$result = $this->connection->query($query);
				Profiler::end();
			}

			catch (PDOException $e) {
				Profiler::store('exception', $e->getMessage(), 'system/exeption');
				Profiler::end();

				$this->lastException = $e;

				$this->log['error'][] = array(
					'query' =>		$query,
					'message' =>	$e->getMessage(),
					'code' =>		$e->getCode()
				);

				throw new DatabaseException(
					__(
						'MySQL Error (%1$s): %2$s in query "%3$s"',
						array($e->getCode(), $e->getMessage(), $query)
					),
					end($this->log['error'])
				);
			}

			return new $return_type($result);
		}

		public function cleanFields(array $array) {
			foreach ($array as $key => $val) {
				$array[$key] = (strlen($val) == 0 ? 'NULL' : "'".$this->escape(trim($val))."'");
			}

			return $array;
		}

		public function lastInsertId() {
			return $this->connection->lastInsertId();
		}

		public function lastError() {
			return array(
				$this->lastException->getCode(),
				$this->lastException->getMessage(),
				$this->lastQuery
			);
		}

		public function lastQuery() {
			return $this->lastQuery;
		}
	}