<?php

	if (!defined('BITTER_LANGUAGE_PATH')) {
		define('BITTER_LANGUAGE_PATH', EXTENSIONS . '/devkit_debug/lib/bitter/languages');
	}

	if (!defined('BITTER_FORMAT_PATH')) {
		define('BITTER_FORMAT_PATH', EXTENSIONS . '/devkit_debug/lib/bitter/formats');
	}

	if (!defined('BITTER_CACHE_PATH')) {
		define('BITTER_CACHE_PATH', EXTENSIONS . '/devkit_debug/lib/bitter/caches');
	}

	require_once(LIB . '/class.cache.php');
	require_once(LIB . '/class.devkit.php');
	require_once(EXTENSIONS . '/devkit_debug/lib/bitter/bitter.php');

	// Create cache folter:
	if (!is_dir(BITTER_CACHE_PATH)) {
		General::realiseDirectory(BITTER_CACHE_PATH);
	}

	class DevKit_Debug extends DevKit {
		protected $show;
		protected $view;
		protected $input;
		protected $output;
		protected $params;
		protected $template;
		protected $utilities;
		protected $url;

		public function __construct(View $view) {
			parent::__construct($view);

			$this->title = __('Debug');
			$this->utilities = $this->findUtilities($this->view->template);

			if (isset($_GET['debug'])) {
				$this->show = ($_GET['debug'] ? $_GET['debug'] : 'source');
			}

			else {
				$this->show = 'frontend';
			}

			unset($this->url->parameters()->debug);
		}

		protected function findUtilities($source) {
			$found = $tree = array();

			$this->findUtilitiesRecursive(
				WORKSPACE, $source,
				$found, $tree
			);

			if (empty($tree)) return array();

			return $tree;
		}

		protected function findUtilitiesRecursive($path, $source, &$found, &$tree) {
			$utilities = array();

			if (preg_match_all('/<xsl:(import|include)\s*href="([^"]*)/i', $source, $matches)) {
				$utilities = $matches[2];
			}

			// Validate paths:
			foreach ($utilities as $index => &$utility) {
				$utility = realpath($path . '/' . $utility);

				if (
					$utility === false
					or in_array($utility, $found)
					or !is_file($utility)
				) continue;

				$source = file_get_contents($utility);
				$sub_tree = array();

				if (trim($source) == '') continue;

				$this->findUtilitiesRecursive(
					dirname($utility), $source,
					$found, $sub_tree
				);

				$found[] = $utility;
				$tree[] = (object)array(
					'file'	=> $utility,
					'tree'	=> $sub_tree
				);
			}
		}


		public function render(Context $parameters, XMLDocument $document, $output) {
			$this->template = $this->view->template;
			$this->output = $output;

			// Force sane headers:
			header('content-type: text/html');

			$document->formatOutput = true;
			$this->input = $document->saveXML();
			$this->params = $parameters;

			return parent::render($parameters, $document);
		}

		protected function appendTitle(DOMElement $wrapper) {
			$title = parent::appendTitle($wrapper);

			if ($this->output) {
				try {
					$document = new DOMDocument('1.0', 'UTF-8');
					$document->loadHTML($this->output);
					$xpath = new DOMXPath($document);
					$value = $xpath->evaluate('string(//title[1])');
				}

				catch (Exception $e) {
					// We really don't care either way.
				}

				if (isset($value)) $title->setValue($value);
			}

			return $title;
		}

		protected function appendIncludes(DOMElement $wrapper) {
			parent::appendIncludes($wrapper);

			$wrapper->appendChild(
				$this->createStylesheetElement(URL . '/extensions/devkit_debug/assets/devkit.css')
			);
			$wrapper->appendChild(
				$this->createScriptElement(ADMIN_URL . '/assets/js/jquery.js')
			);
			$wrapper->appendChild(
				$this->createScriptElement(URL . '/extensions/devkit_debug/assets/jquery.scrollto.js')
			);
			$wrapper->appendChild(
				$this->createScriptElement(URL . '/extensions/devkit_debug/assets/devkit.js')
			);
		}

		protected function appendSidebar(DOMElement $wrapper) {
			$sidebar = parent::appendSidebar($wrapper);
			$fieldset = $sidebar->lastChild;
			$list = $this->document->createElement('ul');
			$url = clone $this->url;

			$url->parameters()->{'debug'} = 'source';

			$this->appendLink(
				$list, __('View Source'),
				(string)$url, ($this->show == 'source')
			);

			$url->parameters()->debug = 'output';
			$this->appendLink(
				$list, __('View Output'),
				(string)$url, ($this->show == 'output')
			);

			$url->parameters()->debug = 'params';
			$this->appendLink(
				$list, __('View Parameters'),
				(string)$url, ($this->show == 'params')
			);

			$url->parameters()->debug = 'frontend';
			$this->appendLink(
				$list, __('View Frontend'),
				(string)$url, ($this->show == 'frontend')
			);

			$fieldset->appendChild($list);

			$fieldset = Widget::Fieldset(__('Templates'));
			$fieldset->addClass('templates');
			$list = $this->document->createElement('ul');

			$url->parameters()->debug = 'template';
			$item = $this->appendLink(
				$list, basename($this->view->templatePathname()),
				(string)$url, ($this->show == 'template')
			);

			$this->appendUtilityLinks($item, $this->utilities);

			$fieldset->appendChild($list);
			$sidebar->appendChild($fieldset);

			return $sidebar;
		}

		protected function appendUtilityLinks(DOMElement $wrapper, $utilities) {
			$url = clone $this->url;
			$list = $this->document->createElement('ul');

			foreach ($utilities as $utility) {
				$path = ltrim(substr($utility->file, strlen(WORKSPACE)), '/.');

				$url->parameters()->debug = $path;
				$item = $this->appendLink(
					$list, basename($utility->file),
					(string)$url, ($this->show == $path)
				);

				if (!empty($utility->tree)) {
					$this->appendUtilityLinks($item, $utility->tree);
				}
			}

			$wrapper->appendChild($list);

			return $list;
		}

		protected function appendContent(DOMElement $wrapper) {
			$content = parent::appendContent($wrapper);
			$source = null; $type = null;

			if ($this->show == 'frontend') {
				$url = clone $this->url;
				$url->parameters()->debug = 'iframe';

				$iframe = $this->document->createElement('iframe');
				$iframe->setAttribute('height', '400');
				$iframe->setAttribute('width', '400');
				$iframe->setAttribute('src', (string)$url);
				$content->appendChild($iframe);

				unset($url->parameters()->{'debug-get-output'});
			}

			else if ($this->show == 'source') {
				$search = $this->document->createElement('div');
				$search->setAttribute('id', 'search');
				$input = Widget::Input('search');
				$search->appendChild($input);
				$content->appendChild($search);

				$this->appendSource($content, $this->input, 'xml');
			}

			else if ($this->show == 'raw') {
				header('content-type: text/xml');
				echo $this->input; exit;
			}

			else if ($this->show == 'output') {
				$this->appendSource($content, $this->output, 'xml');
			}

			else if ($this->show == 'params') {
				$this->appendParams($content);
			}

			else {
				$this->appendFile($content);
			}
		}

		protected function appendParams(DOMElement $wrapper) {
			$container = $this->document->createElement('div');
			$container->setAttribute('id', 'params');
			$table = $this->document->createElement('table');

			if ($this->params instanceof Context) foreach ($this->params as $key => $value) {
				$row = $this->document->createElement('tr');

				$cell = $this->document->createElement('th');
				$cell->appendChild(
					$this->document->createTextNode("\${$key}")
				);
				$row->appendChild($cell);

				$cell = $this->document->createElement('td');
				$cell->appendChild(
					$this->document->createTextNode("'{$value}'")
				);
				$row->appendChild($cell);

				$table->appendChild($row);
			}

			$container->appendChild($table);
			$wrapper->appendChild($container);

			return $container;
		}

		protected function appendFile(DOMElement $wrapper) {
			$valid = false;

			if ($this->show == 'template') {
				$file = VIEWS . '/' . $this->view->templatePathname();
			}

			else {
				$file = WORKSPACE . '/' . $this->show;
			}

			if (realpath($file) !== false) {
				$file = realpath($file);

				// Make sure it's in the workspace:
				if (strpos($file, WORKSPACE) === 0) {
					$valid = true;
				}
			}

			if ($valid and is_file($file)) {
				$this->appendSource($wrapper, file_get_contents($file), 'xsl');
			}

			else {
				throw new Exception(sprintf(
					'Unable to display %s, file is not readable.',
					var_export($file, true)
				));
			}
		}

		protected function appendSource(DOMElement $wrapper, $source, $language = 'xml') {
			$bitter = new Bitter(true);
			$bitter->loadFormat('symphony');
			$bitter->loadLanguage($language);

			$inner = $this->document->createElement('div');
			$inner->setAttribute('id', 'source');

			$source = $bitter->process($source);

			// Encode special characters:
			// TODO: Find a better way. Not urgent.
			$source = str_replace(
				array("\1", "\2", "\3", "\4", "\5"), '', $source
			);

			libxml_use_internal_errors(false);

			$fragment = $this->document->createDocumentFragment();
			$fragment->appendXML(utf8_encode($source));
			$inner->appendChild($fragment);

			$wrapper->appendChild($inner);

			return $inner;
		}
	}

	return 'DevKit_Debug';