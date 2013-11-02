<?php

	class DummyProfiler implements Profiler {
		public function begin($title, $values = array()) {
			return $this;
		}

		public function notice($title, $values = array()) {
			return $this;
		}

		public function end() {
			return $this;
		}
	}