<?php

	require_once(LIB . '/class.event.php');
	require_once(LIB . '/class.documentheaders.php');

	Class ViewException extends Exception {}

	Class ViewFilterIterator extends FilterIterator{
		public function __construct($path=NULL, $recurse=true){
			if(!is_null($path)) $path = VIEWS . '/' . trim($path, '/');
			else $path = VIEWS;

			parent::__construct(
				$recurse == true
					?	new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST)
					:	new DirectoryIterator($path)
			);

		}

		// Only return folders, and only those that have a 'X.config.xml' file within. This characterises a View.
		public function accept(){
			if($this->getInnerIterator()->isDir() == false) return false;
			preg_match('/\/?([^\\\\\/]+)$/', $this->getInnerIterator()->getPathname(), $match); //Find the view handle

			return (is_file(sprintf('%s/%s.config.xml', $this->getInnerIterator()->getPathname(), $match[1])));
		}
	}

	/**
	* Thought process: Views could stay simple and generic to allow for other
	* kinds of views (i.e. non XML/XSLT-powered). Not sure if this makes any
	* sense at all, but I figured I'd try it.
	*/
	class View {
		const ERROR_VIEW_NOT_FOUND = 0;
		const ERROR_FAILED_TO_LOAD = 1;

		/**
		 * Set default headers — can be overwritten by individual view
		 */
		public function setHeaders() {
			$this->headers->append('Content-Type', 'text/xml;charset=utf-8');
			$this->headers->append('Expires', 'Mon, 12 Dec 1982 06:14:00 GMT');
			$this->headers->append('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
			$this->headers->append('Cache-Control', 'no-cache, must-revalidate, max-age=0');
			$this->headers->append('Pragma', 'no-cache');
		}
	}

	/**
	* Considering the above, I decided to add SymphonyView as a class of
	* view that assumes XML/XSLT and assumes a certain kind of view hierarchy.
	* Includes methods and properties common to all XML/XSLT-powered views
	* (both frontend and administration)
	*/
	Class SymphonyView extends View {
		public $context;
		public $document;
		public $handle;
		public $headers;
		public $location;
		public $params;
		public $path;
		public $stylesheet;

		/**
		* Initializes objects and properties common to all SymphonyView
		* objects
		*/
		public function initialize() {
			// Initialize headers
			$this->headers = new DocumentHeaders;
			$this->setHeaders();

			// Initialize context
			$this->context = new Register;

			// Initialize XML
			$this->document = new XMLDocument;
			$this->document->appendChild(
				$this->document->createElement('data')
			);

			//Initialize XSLT
			$this->stylesheet = new XMLDocument;

			Widget::init($this->document);
		}

		/**
		* Parses the URL to figure out what View to load
		*
		* @param	$path		string	View path including URL parameters to attempt to find
		* @param	$expression string	Expression used to match the view driver/conf file. Use printf syntax.
		*/
		public function parseURL($path, $expression = '%s.conf.xml') {
			$parts = preg_split('/\//', $path, -1, PREG_SPLIT_NO_EMPTY);
			$view = null;

			while (!empty($parts)) {
				$part = array_shift($parts);
				$file = sprintf(
					'%s%s/%s/' . $expression,
					$this->location, $view, $part, $part
				);

				if (!is_file($file)) {
					array_unshift($parts, $part);

					break;
				}

				$view = $view . "/{$part}";
			}

			if (is_null($view)) {
				throw new ViewException(__('View, %s, could not be found.', array($path)), self::ERROR_VIEW_NOT_FOUND);
			}

			return $this->loadFromPath($view, (!empty($parts) ? $parts : null));
		}

		/**
		* Builds the context XML and prepends it to $this->document's root
		*/
		public function buildContextXML($root) {
			$element = $this->document->createElement('context');
			$root->prependChild($element);

			foreach($this->context as $key => $item){
				if(is_array($item->value) && count($item->value) > 1){
					$p = $this->document->createElement($key);
					foreach($item->value as $k => $v){
						$p->appendChild($this->document->createElement((string)$k, (string)$v));
					}
					$element->appendChild($p);
				}
			}

			if(strlen(trim($view->template)) == 0){
				$messages->append('template', 'Template is required, and cannot be empty.');
			}
			elseif(!General::validateXML($view->template, $errors)) {

				$fragment = Administration::instance()->Page->createDocumentFragment();

				$fragment->appendChild(new DOMText(
					__('This document is not well formed. The following error was returned: ')
				));
				$fragment->appendChild(Administration::instance()->Page->createElement('code', $errors->current()->message));

				$messages->append('template', $fragment);

			}

			if($messages->length() > 0){
				throw new ViewException(__('View could not be saved. Validation failed.'), self::ERROR_MISSING_OR_INVALID_FIELDS);
			}

			if($simulate != true){
				if(!is_dir(dirname($pathname)) && !mkdir(dirname($pathname), intval(Symphony::Configuration()->main()->system->{'directory-write-mode'}, 8), true)){
					throw new ViewException(
						__('Could not create view directory. Please check permissions on <code>%s</code>.', $view->path),
						self::ERROR_FAILED_TO_WRITE
					);
				}

				// Save the config
				if(!General::writeFile($pathname, (string)$view,Symphony::Configuration()->main()->system->{'file-write-mode'})){
					throw new ViewException(
						__('View configuration XML could not be written to disk. Please check permissions on <code>%s</code>.', $view->path),
						self::ERROR_FAILED_TO_WRITE
					);
				}

				// Save the template file
				$result = General::writeFile(
					sprintf('%s/%s/%s.xsl', VIEWS, $view->path, $view->handle),
					$view->template,
					Symphony::Configuration()->main()->system->{'file-write-mode'}
				);

				if(!$result){
					throw new ViewException(
						__('Template could not be written to disk. Please check permissions on <code>%s</code>.', $view->path),
						self::ERROR_FAILED_TO_WRITE
					);
				}
			}

			return true;
		}

		public function __toString() {
			$doc = new DOMDocument('1.0', 'UTF-8');
			$doc->formatOutput = true;

			$root = $doc->createElement('view');
			$doc->appendChild($root);

			if (!isset($this->guid) || is_null($this->guid)) {
				$this->guid = uniqid();
			}

			$root->setAttribute('guid', $this->guid);

			$root->appendChild($doc->createElement('title', General::sanitize($this->title)));
			$root->appendChild($doc->createElement('content-type', $this->{'content-type'}));

			if (is_array($this->{'url-parameters'}) && count($this->{'url-parameters'}) > 0) {
				$url_parameters = $doc->createElement('url-parameters');

				foreach ($this->{'url-parameters'} as $p) {
					$url_parameters->appendChild($doc->createElement('item', General::sanitize($p)));
				}

				$root->appendChild($url_parameters);
			}

			if (is_array($this->events) && count($this->events) > 0) {
				$events = $doc->createElement('events');

				foreach ($this->events as $p) {
					$events->appendChild($doc->createElement('item', General::sanitize($p)));
				}

				$root->appendChild($events);
			}

			if (is_array($this->{'data-sources'}) && count($this->{'data-sources'}) > 0) {
				$data_sources = $doc->createElement('data-sources');

				foreach ($this->{'data-sources'} as $p) {
					$data_sources->appendChild($doc->createElement('item', General::sanitize($p)));
				}

				$root->appendChild($data_sources);
			}

			if (is_array($this->types) && count($this->types) > 0) {
				$types = $doc->createElement('types');

				foreach ($this->types as $t) {
					$types->appendChild($doc->createElement('item', General::sanitize($t)));
				}
			}
		}

		/**
		* Performs an XSLT transformation on a SymphonyView's $document using its
		* $stylesheet.
		*
		* @param string $directory
		*  Base directory to perform the transformation in
		*
		* @return string containing result of transformation
		*/
		public function transform($directory = null) {
			// Set directory for performing the transformation
			// This is for XSLT import/include I believe.
			// Defaults to root views dir (/workspace/views/ or
			// /symphony/content/). Can be overridden if called
			// with $directory param.
			if (is_null($directory)) {
				$dir = $this->location;
			}

			else {
				$dir = $directory;
			}

			// Get current directory
			$cwd = getcwd();

			// Move to tranformation directory
			chdir($dir);

			// Perform transformation
			$output = XSLProc::transform(
				$this->document->saveXML(),
				$this->stylesheet->saveXML(),
				XSLProc::XML,
				array(), array()
			);

			// Move back to original directory
			chdir($cwd);

			if (XSLProc::hasErrors() && !isset($_REQUEST['debug'])) {
				throw new XSLProcException('Transformation Failed');
			}

			// HACK: Simple debug output:
			if (isset($_REQUEST['debug'])) {
				$this->document->formatOutput = true;

				echo '<pre>', htmlentities($this->document->saveXML()); exit;
			}

			// Return result of transformation
			return $output;
		}
	}

	Class ViewIterator implements Iterator{
		private $_iterator;
		private $_length;
		private $_position;
		private $_current;

		public function __construct($path=NULL, $recurse=true){
			$this->_iterator = new ViewFilterIterator($path, $recurse);
			$this->_length = $this->_position = 0;
			foreach($this->_iterator as $f){
				$this->_length++;
			}
			$this->_iterator->getInnerIterator()->rewind();
		}

		public function current(){
			$path = str_replace(VIEWS, NULL, $this->_iterator->current()->getPathname());

			if(!($this->_current instanceof self) || $this->_current->path != $path){
				$this->_current = new FrontendView();
				$this->_current->loadFromPath($path);
			}
			return $this->_current;
		}

		public function innerIterator(){
			return $this->_iterator;
		}

		public function next(){
			$this->_position++;
			$this->_iterator->next();
		}

		public function key(){
			return $this->_iterator->key();
		}

		public function valid(){
			return $this->_iterator->getInnerIterator()->valid();
		}

		public function rewind(){
			$this->_position = 0;
			$this->_iterator->rewind();
		}

		public function position(){
			return $this->_position;
		}

		public function length(){
			return $this->_length;
		}
	}
