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

	function trim_path($path, $root) {
		if (strpos($path, $root) === 0) {
			return substr($path, strlen($root));
		}

		else {
			return trim_path($path, dirname($root));
		}
	}

	class DevKit_Profiler extends DevKit {
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

			$this->title = __('Profiler');

			unset($this->url->parameters()->profiler);
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

		public function render(Context $parameters, XMLDocument $document = null) {
			$this->template = $this->view->template;

			try {
				$this->output = $this->view->render($parameters, $document);
			}

			catch (Exception $e) {
				//throw $e;
			}

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
			$wrapper->appendChild(
				$this->createStylesheetElement(URL . '/extensions/devkit_profiler/assets/fonts.css')
			);
			$wrapper->appendChild(
				$this->createStylesheetElement(URL . '/extensions/devkit_profiler/assets/styles.css')
			);
			$wrapper->appendChild(
				$this->createScriptElement(URL . '/extensions/devkit_profiler/assets/jquery.js')
			);
			$wrapper->appendChild(
				$this->createScriptElement(URL . '/extensions/devkit_profiler/assets/sprintf.js')
			);
			$wrapper->appendChild(
				$this->createScriptElement(URL . '/extensions/devkit_profiler/assets/scripts.js')
			);
		}

		protected function appendContent(DOMElement $wrapper) {
			$script = $this->document->createElement('script');
			$script->setValue(sprintf('Profiler.prepare(%s);', json_encode(Profiler::results())));
			$wrapper->appendChild($script);
		}

		protected function appendSidebar(DOMElement $wrapper) {

		}
	}

	return 'DevKit_Profiler';