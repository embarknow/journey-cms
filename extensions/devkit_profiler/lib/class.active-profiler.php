<?php

	class ActiveProfiler implements Profiler {
		protected $stack;
		protected $current;

		public function __construct() {
			$this->stack = array();
			$this->current = (object)array(
				'children'	=> array()
			);
		}

		public function begin($title, $values = array()) {
			$parent = $this->current;
			$this->current = (object)array(
				'title'		=> vsprintf($title, $values),
				'start'		=> microtime(true),
				'end'		=> null,
				'memory'	=> null,
				'children'	=> array()
			);

			array_push($this->stack, $parent);

			$parent->children[] = $this->current;

			return $this;
		}

		public function notice($title, $values = array()) {
			$this->current->children[] = (object)array(
				'title'		=> vsprintf($title, $values),
				'time'		=> microtime(true),
				'memory'	=> memory_get_usage()
			);

			return $this;
		}

		public function end() {
			$this->current->end = microtime(true);
			$this->current->memory = memory_get_usage();
			$parent = array_pop($this->stack);

			if ($parent) $this->current = $parent;

			return $this;
		}
	}