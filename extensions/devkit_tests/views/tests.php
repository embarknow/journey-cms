<?php

	require_once LIB . '/class.cache.php';
	require_once LIB . '/class.devkit.php';
	require_once EXTENSIONS . '/devkit_tests/lib/class.reflectioncomment.php';
	require_once EXTENSIONS . '/devkit_tests/lib/class.symphonytest.php';

	class DevkitTests extends DevKit {
		protected $extensions;
		protected $handle;
		protected $parent;
		protected $target;
		protected $tests;
		protected $view;

		public function __construct(View $view) {
			$extensions = new ExtensionIterator();

			parent::__construct($view);
			unset($this->url->parameters()->tests);

			$this->title = __('Tests');
			$this->target = strlen(trim($_GET['tests'])) == 0
				? null :
				$_GET['tests'];

			list($this->parent, $this->handle) = explode('.', $this->target);

			$this->extensions['workspace'] = __('Workspace');
			$this->extensions['symphony'] = __('Symphony');

			foreach (new SymphonyTestIterator() as $filename) {
				$test = SymphonyTest::load($filename);
				$info = SymphonyTest::readInformation($test);
				$info->instance = $test;
				$info->handle = $test->handle;

				if ($info->{'in-symphony'} == true) {
					$info->parent = 'symphony';
				}

				else if ($info->{'in-workspace'} == true) {
					$info->parent = 'workspace';
				}

				else if ($info->{'in-extension'} == true) {
					$info->parent = $info->extension;

					if (isset($this->extensions[$info->extension]) === false) {
						$extension = $extensions[$info->extension]->about();
						$this->extensions[$info->extension] = $extension->name;
					}
				}

				$this->tests[$info->parent . '.' . $info->handle] = $info;
			}
		}

		protected function appendIncludes(DOMElement $wrapper) {
			parent::appendIncludes($wrapper);

			$wrapper->appendChild(
				$this->createStylesheetElement(URL . '/extensions/devkit_tests/assets/devkit.css')
			);
			$wrapper->appendChild(
				$this->createScriptElement(ADMIN_URL . '/assets/js/jquery.js')
			);
		}

		protected function appendContent(DOMElement $wrapper) {
			$content = parent::appendContent($wrapper);
			$document = $this->document;

			// Index page:
			if ($this->target === null) {
				$content->appendChild($document->createElement('h1', __('All Tests')));

				foreach ($this->extensions as $handle => $name) {
					$this->appendTests($content, $handle, 'h2');
				}
			}

			// Sub index page:
			else if (
				$this->target == 'workspace'
				|| $this->target == 'symphony'
				|| isset($this->extensions[$this->target])
			) {
				$this->appendTests($content, $this->target);
			}

			// View a test:
			else if (isset($this->tests[$this->target])) {
				$test = $this->tests[$this->target];
				$reporter = new SymphonyTestReporter($document);

				$test->instance->run($reporter);

				$content->appendChild($document->createElement(
					'h1', $test->name
				));

				$fieldset = $document->createElement('fieldset');
				$fieldset->setAttribute('class', 'settings');
				$fieldset->appendChild($document->createElement(
					'legend', __('Description')
				));

				$description = $document->createElement('p');
				$description->setValue($test->description);
				$fieldset->appendChild($description);

				$content->appendChild($fieldset);
				$content->appendChild($reporter->getFieldset());
			}
		}

		public function appendTests(DOMElement $wrapper, $parent, $heading = 'h1') {
			$document = $this->document;
			$list = $document->createElement('dl');
			$found = false;

			foreach ($this->tests as $target => $test) {
				if ($test->parent != $parent) continue;

				$title = $document->createElement('dt');
				$link = $document->createElement('a', $test->name);
				$link->setAttribute('href', '?tests=' . $target . $this->_query_string);
				$title->appendChild($link);

				$description = $document->createElement('dd', $test->description);

				$list->appendChild($title);
				$list->appendChild($description);

				$found = true;
			}

			if ($found === true) {
				if (isset($this->extensions[$parent])) {
					$wrapper->appendChild($document->createElement(
						$heading, $this->extensions[$parent]
					));
				}

				$wrapper->appendChild($list);
			}
		}

		protected function appendSidebar(DOMElement $wrapper) {
			$document = $this->document;
			$sidebar = parent::appendSidebar($wrapper);
			$fieldset = $sidebar->lastChild;
			$list = $document->createElement('ul');
			$fieldset->appendChild($list);
			$url = clone $this->url;
			$extensions = array();

			$url->parameters()->{'tests'} = null;
			$this->appendLink(
				$list,
				__('All Tests'),
				(string)$url,
				($this->target == null)
			);

			foreach ($this->tests as $target => $info) {
				if (isset($extensions[$info->parent]) === false) {
					$url->parameters()->{'tests'} = $info->parent;
					$extension_item = $this->appendLink(
						$list,
						$this->extensions[$info->parent],
						(string)$url,
						($this->target == $info->parent)
					);

					$extensions[$info->parent] = $document->createElement('ul');
					$extension_item->appendChild($extensions[$info->parent]);
				}

				if ($this->parent == $info->parent) {
					$url->parameters()->{'tests'} = $target;
					$extension_item = $this->appendLink(
						$extensions[$info->parent],
						$info->name,
						(string)$url,
						($this->target == $target)
					);
				}
			}

			return $sidebar;
		}
	}

	return 'DevkitTests';