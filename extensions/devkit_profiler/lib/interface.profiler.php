<?php

	interface Profiler {
		public function begin($title, $values = array());
		public function notice($title, $values = array());
		public function end();
	}