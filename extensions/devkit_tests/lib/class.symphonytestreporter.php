<?php

	/**
	 * @package lib
	 */

	/**
	 * The SymphonyTestReporter class extends SimpleReporter to build a test case
	 * report using XMLElements instead of string concatenation.
	 */
	class SymphonyTestReporter extends SimpleReporter {
		/**
		 * The report parent element.
		 */
		protected $fieldset;

		/**
		 * The internal list of executed tests, errors, passes and skips.
		 */
		protected $list;

		/**
		 * The XML document to create elements in.
		 */
		protected $document;

		/**
		 * Prepares a new fieldset.
		 * @access public
		 */
		public function __construct(DOMDocument $document, $character_set = 'UTF-8') {
			parent::__construct();

			$this->document = $document;
			$this->fieldset = $document->createElement('fieldset');
			$this->fieldset->setAttribute('class', 'settings');
			$this->fieldset->appendChild($document->createElement(
				'legend', __('Results')
			));
			$this->list = $document->createElement('dl');
			$this->list->setAttribute('class', 'stack');
		}

		/**
		 * Fetch the internal fieldset.
		 * @access public
		 */
		public function getFieldset() {
			return $this->fieldset;
		}

		public function getTestList() {
			$items = parent::getTestList();
			$breadcrumb = array();
			array_shift($items);

			foreach ($items as $item) {
				if (realpath($item) !== false) continue;

				// Maybe it's a class method?
				if (isset($class) && $class->hasMethod($item)) {
					$method = $class->getMethod($item);
					$comment = new ReflectionComment($method);

					if ($comment->hasTitle()) {
						$item = $comment->getTitle();
					}
				}

				// Maybe it's a class?
				else if (class_exists($item)) {
					$class = new ReflectionClass($item);
					$comment = new ReflectionComment($class);

					if ($comment->hasTitle()) {
						$item = $comment->getTitle();
					}
				}

				$breadcrumb[] = $item;
			}

			return $breadcrumb;
		}

		/**
		 * Paints the start of a test method.
		 * @param string $test_name Name of test or other label.
		 * @access public
		 */
		public function paintMethodStart($test_name) {
			parent::paintMethodStart($test_name);

			$breadcrumb = $this->getTestList();

			$item = $this->document->createElement('dt');
			$item->setAttribute('class', 'breadcrumb');
			$item->setValue(sprintf(
				'%s ->',
				implode(' -> ', $breadcrumb)
			));
			$this->list->appendChild($item);
		}

		/**
		 * The footer represents the end of reporting, all elements are linked here.
		 * @param string $test_name Name class of test.
		 * @access public
		 */
		public function paintFooter($test_name) {
			$failed = ($this->getFailCount() + $this->getExceptionCount() > 0);

			if ($this->list->hasChildNodes()) {
				$this->fieldset->appendChild($this->list);
			}

			$result = $this->document->createElement('p');
			$fragment = $this->document->createDocumentFragment();
			$fragment->appendXML(sprintf(
				'<strong>%d</strong> of <strong>%d</strong> test cases complete: <strong>%d</strong> passes, <strong>%d</strong> fails and <strong>%d</strong> exceptions.',

				$this->getTestCaseProgress(),
				$this->getTestCaseCount(),
				$this->getPassCount(),
				$this->getFailCount(),
				$this->getExceptionCount()
			));
			$result->appendChild($fragment);

			if ($failed) {
				$result->setAttribute('class', 'result failed');
			}

			else {
				$result->setAttribute('class', 'result success');
			}

			$this->fieldset->appendChild($result);
		}

		/**
		 * Add a PHP error message to the message list.
		 * @param string $message The message to output.
		 * @access public
		 */
		public function paintError($message) {
			parent::paintError($message);

			$item = $this->document->createElement('dd');
			$item->setAttribute('class', 'message bad');
			$item->setValue($message);
			$this->list->appendChild($item);
		}

		/**
		 * Add an exception message to the message list.
		 * @param Exception $exception Used to generate the message.
		 * @access public
		 */
		public function paintException($exception) {
			parent::paintException($exception);

			$item = $this->document->createElement('dd');
			$item->setAttribute('class', 'message bad');
			$item->setValue(sprintf(
				'Unexpected exception [%d] of type [%s] with message [%s] at [%s line %d]',
				$this->getExceptionCount(),
				get_class($exception),
				$exception->getMessage(),
				$exception->getFile(),
				$exception->getLine()
			));
			$this->list->appendChild($item);
		}

		/**
		 * Add an assertion failure to the message list.
		 * @param string $message The message to output.
		 * @access public
		 */
		public function paintFail($message) {
			parent::paintFail($message);

			$item = $this->document->createElement('dd');
			$item->setAttribute('class', 'message bad');
			$item->setValue(trim($message));
			$this->list->appendChild($item);
		}

		/**
		 * Add debuging messages to the message list.
		 * @param string $message The message to output.
		 * @access public
		 */
		public function paintFormattedMessage($message) {
			$item = $this->document->createElement('dd');
			$item->setAttribute('class', 'message');
			$item->setValue($message);
			$this->list->appendChild($item);
		}

		/**
		 * Add an assertion pass to the message list.
		 * @param string $message The message to output.
		 * @access public
		 */
		public function paintPass($message) {
			parent::paintPass($message);

			$item = $this->document->createElement('dd');
			$item->setAttribute('class', 'message good');
			$item->setValue(trim($message));
			$this->list->appendChild($item);
		}

		/**
		 * Add an assertion skip to the message list.
		 * @param string $message The message to output.
		 * @access public
		 */
		public function paintSkip($message) {
			parent::paintSkip($message);

			$item = $this->document->createElement('dd');
			$item->setAttribute('class', 'message skip');
			$item->setValue(trim($message));
			$this->list->appendChild($item);
		}
	}