<?php

	class Profiler {
		static protected $current;
		static protected $disabled = true;
		static protected $results = array();
		static protected $stack = array();

		static public function enable() {
			self::$disabled = false;
			self::$current = null;
		}

		static public function disable() {
			while (self::$stack) self::end();

			if (
				isset(self::$current->data)
				&& isset(self::$current->data->{'time-end'}) === false
			) {
				self::end();
			}

			self::$disabled = true;
		}

		static public function begin($description) {
			if (self::$disabled === true) return;

			$arguments = array_slice(func_get_args(), 1);
			$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);

			if (isset(self::$current) === true) {
				self::$stack[] = $parent = self::$current;
			}

			$current = (object)array(
				'description' =>	$description,
				'data' =>			(object)array(
					'memory-begin' =>	array(
											array('system/memory', 'data/number', 'data/bytes'),
											memory_get_usage()
										),
					'time-begin' =>		array(
											array('system/time', 'data/number', 'data/microseconds'),
											microtime(true)
										)
				),
				'results' =>		array()
			);

			if (isset($backtrace[0]['file'], $backtrace[0]['line'])) {
				$current->data->{'backtrace'} = array(
					array('system/resource'),
					sprintf(
						'%s:%s',
						$backtrace[0]['file'],
						$backtrace[0]['line']
					)
				);
			}

			if (isset(self::$current) === false) {
				self::$results[] = $current;
			}

			self::$current = $current;

			if (isset($parent->results)) {
				$parent->results[] = self::$current;
			}
		}

		static public function end() {
			if (self::$disabled === true) return;

			$current = self::$current;
			$parent = array_pop(self::$stack);

			$current->data->{'memory-end'} = array(
				array('system/memory', 'data/number', 'data/bytes'),
				memory_get_usage()
			);
			$current->data->{'time-end'} = array(
				array('system/time', 'data/number', 'data/microseconds'),
				microtime(true)
			);

			if ($parent) {
				self::$current = $parent;
			}
		}

		static public function notice($title) {
			if (self::$disabled === true) return;

			$arguments = array_slice(func_get_args(), 1);

			$result = (object)array(
				'description' =>	vsprintf($title, $arguments),
				'data' =>			(object)array(
					'memory-begin' =>	array(
											array('system/memory', 'data/number', 'data/bytes'),
											memory_get_usage()
										),
					'time-begin' =>		array(
											array('system/time', 'data/number', 'data/microseconds'),
											microtime(true)
										)
				)
			);
			$result->data->{'memory-end'} = $result->data->{'memory-begin'};
			$result->data->{'time-end'} = $result->data->{'time-begin'};

			self::$current->results[] = $result;
		}

		static public function results() {
			return self::$results;
		}

		static public function store($name, $data, $type = null) {
			if (self::$disabled === true) return;

			if (isset($type) === false) {
				$type = array();

				if (is_object($data)) {
					$type[] = 'data/object';
				}

				else if (is_array($data)) {
					$type[] = 'data/array';
				}

				else if (is_bool($data)) {
					$type[] = 'data/boolean';
				}

				else if (is_string($data)) {
					$type[] = 'data/string';
				}

				else if (is_integer($data) || is_float($data)) {
					$type[] = 'data/number';
				}

				else {
					$type[] = 'data/other';
				}
			}

			else {
				$type = explode(' ', $type);
			}

			self::$current->data->{$name} = array($type, $data);
		}
	}