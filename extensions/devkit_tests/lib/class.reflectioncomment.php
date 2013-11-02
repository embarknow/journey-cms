<?php

	class ReflectionComment {
		public $text;
		public $raw;

		public function __construct(Reflector $reflection) {
			$trim_syntax = function($item) {
				return preg_replace('%^(/[*]{2}|\s*[*]/|\s*[*]+\s?)%', null, $item);
			};

			$this->raw = $reflection->getDocComment();
			$lines = explode("\n", $this->raw);
			$lines = array_map($trim_syntax, $lines);
			$this->text = trim(implode("\n", $lines));
		}

		public function getCommentText() {
			return $this->text;
		}

		public function getCommentRaw() {
			return $this->raw;
		}

		public function hasDescription() {
			return $this->getDescription() !== null;
		}

		public function hasTitle() {
			return $this->getTitle() !== null;
		}

		public function getDescription() {
			$comment = $this->text;

			if (preg_match('%^[^\n]+%', $comment, $match)) {
				$comment = trim(substr($comment, strlen($match[0])));
			}

			if ($comment === '') {
				$comment = null;
			}

			return $comment;
		}

		public function getTitle() {
			$first = null;

			if (preg_match('%^[^\n]+%', $this->text, $match)) {
				$first = trim($match[0]);
			}

			if ($first === '') {
				$first = null;
			}

			return $first;
		}
	}