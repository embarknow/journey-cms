<?php

use Embark\CMS\SystemDateTime;

	class Log {
		const NOTICE = E_NOTICE;
		const WARNING = E_WARNING;
		const ERROR = E_ERROR;

		const APPEND = 10;
		const OVERWRITE = 11;

		private static $__errorTypeStrings = array (
			E_NOTICE =>         		'Notice',
			E_WARNING =>        		'Warning',
			E_ERROR =>          		'Error',
			E_PARSE =>          		'Parsing Error',

			E_CORE_ERROR =>     		'Core Error',
			E_CORE_WARNING =>   		'Core Warning',
			E_COMPILE_ERROR =>  		'Compile Error',
			E_COMPILE_WARNING => 		'Compile Warning',

			E_USER_NOTICE =>    		'User Notice',
			E_USER_WARNING =>   		'User Warning',
			E_USER_ERROR =>     		'User Error',

			E_STRICT =>         		'Strict Notice',
			E_RECOVERABLE_ERROR =>  	'Recoverable Error'
		);

		private $_log_path;
		private $_log;
		private $_max_size;
		private $_archive;

		public function __construct($logpath) {
			$this->setLogPath($logpath);
			$this->setArchive(false);
			$this->setMaxSize(-1);
		}

		public function setLogPath($path){
			$this->_log_path = $path;
		}

		public function getLogPath(){
			return $this->_log_path;
		}

		public function setArchive($bool){
			$this->_archive = $bool;
		}

		public function setMaxSize($size){
			$this->_max_size = $size;
		}

		private function __defineNameString($type) {
			if ($type instanceof ErrorException) {
				$type = $type->getCode();
			}

			else if ($type instanceof Exception) {
				return get_class($type);
			}

			if (isset(self::$__errorTypeStrings[$type])) {
				return self::$__errorTypeStrings[$type];
			}

			return 'UNKNOWN';
		}

		public function pushToLog($message, $type = E_NOTICE, $writeToLog = false, $addbreak = true, $append = false) {
			if (empty($this->_log) && is_array($this->_log) === false) {
				$this->_log = array();
			}

			if ($append) {
				$this->_log[count($this->_log) - 1]['message'] = (
					$this->_log[count($this->_log) - 1]['message'] . $message
				);

				$message = '    ' . $message;
			}

			else {
				array_push($this->_log, array(
					'type' =>		$type,
					'time' =>		time(),
					'message' =>	$message
				));

				$message = (new SystemDateTime)->format('H:i:s/d') . ' > ' . $this->__defineNameString($type) . ":\n    " . $message;
			}

			if ($writeToLog) $this->writeToLog($message, $addbreak);
		}

		public function pushExceptionToLog(Exception $exception) {
			$trace = $exception->getTrace();

			array_unshift($trace, array(
				'class' =>		get_class($exception),
				'function' =>	'__construct',
				'file' =>		$exception->getFile(),
				'line' =>		$exception->getLine()
			));

			$this->pushToLog($exception->getMessage(), $exception, true);
			$this->pushTraceToLog($trace);
		}

		protected function pushTraceToLog(array $trace) {
			$pad = strlen(count($trace)) + 1;

			foreach ($trace as $index => $data) {
				$item = str_pad('#' . ($index + 1), $pad, ' ', STR_PAD_LEFT);

				if (isset($data['class'], $data['function'])) {
					$callback = sprintf('%s::%s', $data['class'], $data['function']);
				}

				// Lone function:
				else if (isset($data['function'])) {
					$callback = $data['function'];
				}

				else {
					$callback = '{unknown}';
				}

				$info = sprintf(
					'%s %s',
					$item,
					$callback
				);
				$file = sprintf(
					'%s %s:%s',
					str_repeat(' ', $pad),
					(isset($data['file']) ? $data['file'] : '{unknown}'),
					(isset($data['line']) ? $data['line'] : '{unknown}')
				);

				$this->pushToLog($info, $exception, true, true, true);
				$this->pushToLog($file, $exception, true, true, true);
			}
		}

		public function popFromLog() {
			if (count($this->_log) != 0) {
				return array_pop($this->_log);
			}

			return false;
		}

		public function writeToLog($message, $addbreak = true) {
			if (($handle = @fopen($this->_log_path, 'a')) === false) {
				$this->pushToLog("Could Not Open Log File '".$this->_log_path."'", self::ERROR);

				return false;
			}

			if (@fwrite($handle, $message . ($addbreak ? "\r\n" : '')) === false) {
				$this->pushToLog('Could Not Write To Log', self::ERROR);

				return false;
			}

			@fclose($handle);

			return true;
		}

		public function getLog() {
			return $this->_log;
		}

		public function open($mode = self::APPEND) {
			if (is_file($this->_log_path) === false) $mode = self::OVERWRITE;

			if ($mode == self::APPEND) {
				if ($this->_max_size > 0 && @filesize($this->_log_path) > $this->_max_size) {
					$mode = self::OVERWRITE;

					if ($this->_archive) {
						$handle = gzopen(LOGS . '/main.' . (new SystemDateTime)->format('Ymdh') . '.gz', 'w9');
						gzwrite($handle, @file_get_contents($this->_log_path));
						gzclose($handle);
					}
				}
			}

			if ($mode == self::OVERWRITE) {
				@unlink($this->_log_path);

				$this->writeToLog('============================================', true);
				$this->writeToLog('Log Created: ' . (new SystemDateTime)->format(DateTime::W3C), true);
				$this->writeToLog('============================================', true);

				return 1;
			}

			return 2;
		}

		public function close(){
			$this->writeToLog('============================================', true);
			$this->writeToLog('Log Closed: ' . (new SystemDateTime)->format(DateTime::W3C), true);
			$this->writeToLog("============================================\r\n\r\n\r\n", true);
		}
	}

