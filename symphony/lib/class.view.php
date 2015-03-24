<?php

use Embark\CMS\Actors\DatasourceInterface;

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

	Class View{

		const ERROR_VIEW_NOT_FOUND = 0;
		const ERROR_FAILED_TO_LOAD = 1;
		const ERROR_DOES_NOT_ACCEPT_PARAMETERS = 2;
		const ERROR_TOO_MANY_PARAMETERS = 3;

		const ERROR_MISSING_OR_INVALID_FIELDS = 4;
		const ERROR_FAILED_TO_WRITE = 5;

		private $_about;
		private $_path;
		private $_pathname;
		private $_parent;
		private $_parameters;
		private $_template;
		private $_handle;
		private $_guid;

		public function __construct(){

			$this->_about = new StdClass;
			$this->_parameters = new StdClass;

			$this->_path = $this->_parent = $this->_template = $this->_handle = $this->_guid = NULL;
			$this->types = array();
			$this->{'content-type'} = 'text/html;charset=utf-8';
		}

		public function about(){
			return $this->_about;
		}

		public function parameters(){
			return $this->_parameters;
		}

		public function __isset($name){
			if(in_array($name, array('path', 'template', 'handle', 'guid', 'pathname'))){
				return isset($this->{"_{$name}"});
			}
			return isset($this->_about->$name);
		}

		public function __get($name){
			if(in_array($name, array('path', 'template', 'handle', 'guid', 'pathname'))){
				return $this->{"_{$name}"};
			}

			if (!isset($this->_about->$name)) {
				return false;
			}

			return $this->_about->$name;
		}

		public function __set($name, $value){
			if(in_array($name, array('path', 'template', 'handle', 'guid', 'pathname'))){
				$this->{"_{$name}"} = $value;
			}
			else $this->_about->$name = $value;
		}

		public static function loadFromPath($path, array $params=NULL){

			$view = new self;

			$view->path = trim($path, '\\/');

			preg_match('/\/?([^\\\\\/]+)$/', $path, $match); //Find the view handle
			$view->handle = $match[1];

			$pathname = sprintf('%s/%s/%s.config.xml', VIEWS, $view->path, $view->handle);

			if(!is_file($pathname)){
				throw new ViewException(__('View, %s, could not be found.', array($pathname)), self::ERROR_VIEW_NOT_FOUND);
			}

			$doc = @simplexml_load_file($pathname);

			if(!($doc instanceof SimpleXMLElement)){
				throw new ViewException(__('Failed to load view configuration file: %s', array($pathname)), self::ERROR_FAILED_TO_LOAD);
			}

			foreach($doc as $name => $value){
				if(isset($value->item)){
					$stack = array();
					foreach($value->item as $item){
						array_push($stack, (string)$item);
					}
					$view->$name = $stack;
				}
				else $view->$name = (string)$value;
			}

			if(isset($doc->attributes()->guid)){
				$view->guid = (string)$doc->attributes()->guid;
			}
			else{
				$view->guid = uniqid();
			}

			if(!is_null($params)){

				if(!is_array($view->{'url-parameters'}) || count($view->{'url-parameters'}) <= 0){
					throw new ViewException(__('This view does not accept parameters.', array($pathname)), self::ERROR_DOES_NOT_ACCEPT_PARAMETERS);
				}

				if(count($params) > count($view->{'url-parameters'})){
					throw new ViewException(__('Too many parameters supplied.', array($pathname)), self::ERROR_TOO_MANY_PARAMETERS);
				}

				foreach($params as $index => $p){
					$view->setParameter($view->{'url-parameters'}[$index], $p);
				}
			}

			$template = sprintf('%s/%s/%s.xsl', VIEWS, $view->path, $view->handle);
			if(file_exists($template) && is_readable($template)){
				$view->pathname = $template;
				$view->template = file_get_contents($template);
			}

			return $view;
		}

		public function templatePathname(){
			return sprintf('%s/%s.xsl', $this->path, $this->handle);
		}

		public function setParameter($name, $value){
			$this->_parameters->$name = $value;
		}

		public static function loadFromFieldsArray($fields){

			$view = new self;

			foreach($fields as $name => $value){
				$view->$name = $value;
			}

			return $view;
		}

		public static function findFromType($type){
			$views = array();
			foreach(new ViewIterator as $v){
				if(@in_array($type, $v->types)){
					$views[$v->guid] = $v;
				}
			}
			return $views;
		}

		public static function fetchUsedTypes(){
			$types = array();
			foreach(new ViewIterator as $v){
				$types = array_merge((array)$v->types, $types);
			}
			return General::array_remove_duplicates($types);
		}

		public function isChildOf(View $view){
			$current = $this->parent();

			while(!is_null($current)){
				if($current->guid == $view->guid) return true;
				$current = $current->parent();
			}

			return false;
		}

		public static function loadFromURL($path){
			$parts = preg_split('/\//', $path, -1, PREG_SPLIT_NO_EMPTY);
			$view = NULL;

			while(!empty($parts)){

				$p = array_shift($parts);

				if(!is_dir(VIEWS . $view . "/{$p}")){
					array_unshift($parts, $p);
					break;
				}

				$view = $view . "/{$p}";

			}

			return self::loadFromPath($view, (!empty($parts) ? $parts : NULL));
		}

		public static function buildPageTitle(View $v){

			$title = $v->title;

			$current = $v->parent();

			while(!is_null($current)){
				$title = sprintf('%s: %s', $current->title, $title);
				$current = $current->parent();
			}

			return $title;
		}

		public static function countParents(View $v) {
			$current = $v->parent();
			$count = 0;

			while (!is_null($current)) {
				$current = $current->parent();
				$count++;
			}

			return $count;
		}

		public static function move(self $view, $dest){
			$bits = preg_split('~\/~', $dest, -1, PREG_SPLIT_NO_EMPTY);
			$handle = $bits[count($bits) - 1];

			// Config
			rename(
				sprintf('%s/%s/%s.config.xml', VIEWS, $view->path, $view->handle),
				sprintf('%s/%s/%s.config.xml', VIEWS, $view->path, $handle)
			);

			// Template
			rename(
				sprintf('%s/%s/%s.xsl', VIEWS, $view->path, $view->handle),
				sprintf('%s/%s/%s.xsl', VIEWS, $view->path, $handle)
			);

			// Folder
			rename(
				sprintf('%s/%s/', VIEWS, $view->path),
				sprintf('%s/%s/', VIEWS, implode('/', $bits))
			);

			$view->path = implode('/', $bits);
			$view->handle = $handle;
		}

		public static function save(self $view, MessageStack &$messages, $simulate=false){

			if(!isset($view->title) || strlen(trim($view->title)) == 0){
				$messages->append('title', __('Title is required.'));
			}

			$pathname = sprintf('%s/%s/%s.config.xml', VIEWS, $view->path, $view->handle);

			if(file_exists($pathname)){
				$existing = self::loadFromPath($view->path);
				if($existing->guid != $view->guid){
					$messages->append('handle', 'A view with that handle already exists.');
				}
				unset($existing);
			}

			if(isset($view->types) && is_array($view->types) && (bool)array_intersect($view->types, array('index', '404', '403'))){
				foreach($view->types as $t){
					switch($t){
						case 'index':
						case '404':
						case '403':
							$views = self::findFromType($t);
							if(isset($views[$view->guid])) unset($views[$view->guid]);

							if(!empty($views)){
								$messages->append('types', __('A view of type "%s" already exists.', array($t)));
								break 2;
							}
							break;
					}
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

				$root->appendChild($types);
			}

			return $doc->saveXML();
		}

		public function parent() {
			if ($this->_path == $this->handle) {
				return null;
			}

			else if (!($this->_parent instanceof self)) {
				$this->_parent = self::loadFromPath(preg_replace("~/{$this->handle}~", NULL, $this->_path));
			}

			return $this->_parent;
		}

		public function children() {
			return new ViewIterator($this->path, false);
		}

		public static function delete($path, $cascade = false) {
			$view = self::loadFromPath($path);

			if ($cascade == false) {
				foreach ($view->children() as $child) {
					$bits = preg_split('~\/~', $child->path, -1, PREG_SPLIT_NO_EMPTY);
					unset($bits[count($bits) - 2]);
					View::move($child, trim(implode('/', $bits), '/'));
				}
			}

			General::rmdirr(VIEWS . '/' . trim($path, '/'));
		}

		private function __cbSortEventsByPriority($a, $b) {
			if ($a->priority() == $b->priority()) {
		        return 0;
		    }

		    return (($a->priority() > $b->priority()) ? -1 : 1);
		}

		public function render(Context $Parameters, XMLDocument $Document = null, DocumentHeaders $Headers = null) {
			Profiler::begin('Preparing view');

			$outputParameters = new Context;

			if (!is_null($Headers)) {
				$Headers->append('Content-Type', $this->{'content-type'});
			}

			else {
				header('Content-Type: ' . $this->{'content-type'});
			}

			if (is_null($Document)) {
				$Document = new XMLDocument;
				$Document->appendChild($Document->createElement('data'));
			}

			$root = $Document->documentElement;
			$datasources = $events = array();

			if (is_array($this->about()->{'events'}) && !empty($this->about()->{'events'})) {
				$events = $this->about()->{'events'};
			}

			if (is_array($this->about()->{'data-sources'}) && !empty($this->about()->{'data-sources'})) {
				$datasources = $this->about()->{'data-sources'};
			}

			Profiler::end();
			Profiler::begin('Executing events');

			####
			# Delegate: FrontendEventsAppend
			# Description: Append additional Events.
			# Global: Yes
			Extension::notify(
				'FrontendEventsAppend', '/frontend/', array(
					'events'	=> &$events
				)
			);

			if (!empty($events)) {
				$postdata = General::getPostData();
				$events_ordered = array();
				$events_loaded = array();

				foreach ($events as $handle) {
					if (in_array($handle, $events_loaded)) continue;

					if ($handle instanceof Event) {
						$events_ordered[] = $handle;
					}

					else {
						$events_ordered[] = Event::loadFromHandle($handle);
						$events_loaded[] = $handle;
					}
				}

				uasort($events_ordered, array($this, '__cbSortEventsByPriority'));

				foreach ($events_ordered as $event) {
					if (false === $event->canTrigger($postdata)) continue;

					$reflection = new ReflectionObject($event);

					Profiler::begin('Executed event %event');
					Profiler::store('event', $event->handle, 'system/event');
					Profiler::store('class', get_class($event), 'system/class');
					Profiler::store('location', $reflection->getFileName(), 'system/resource action/executed');

					$fragment = $event->trigger($outputParameters, $postdata);

					if ($fragment instanceof DOMDocument && !is_null($fragment->documentElement)) {
						$node = $Document->importNode($fragment->documentElement, true);
						$root->appendChild($node);
					}

					Profiler::end();
				}
			}

			Profiler::end();
			Profiler::begin('Executing datasources');

			####
			# Delegate: FrontendDataSourceAppend
			# Description: Append additional DataSources.
			# Global: Yes
			Extension::notify(
				'FrontendDataSourcesAppend', '/frontend/', array(
					'datasources'	=> &$datasources
				)
			);

			//	Find dependancies and order accordingly
			$datasource_pool = array();
			$dependency_list = array();
			$datasources_ordered = array();
			$all_dependencies = array();

			foreach ($datasources as $handle) {
				$datasource_pool[$handle] = Datasource::loadFromHandle($handle);
				$dependency_list[$handle] = (
					isset($datasource_pool[$handle]['dependencies'])
						? $datasource_pool[$handle]['dependencies']
						: []
				);
			}

			$datasources_ordered = General::dependenciesSort($dependency_list);

			if (!empty($datasources_ordered)) {
				foreach ($datasources_ordered as $handle) {
					$datasource = $datasource_pool[$handle];
					$reflection = new ReflectionObject($datasource);

					try {
						Profiler::begin('Executed datasource %datasource');
						Profiler::store('datasource', $handle, 'system/datasource');
						Profiler::store('class', get_class($datasource), 'system/class');
						Profiler::store('location', $reflection->getFileName(), 'system/resource action/executed');

						if ($datasource->canExecute()) {
							$fragment = $datasource->execute($outputParameters);
						}

						Profiler::end();
					}

					catch (FrontendPageNotFoundException $e) {
						Profiler::store('exception', $e->getMessage(), 'system/exeption');
						Profiler::end();

						FrontendPageNotFoundExceptionHandler::render($e);
					}

					if ($fragment instanceof DomDocumentFragment) {
						$node = $Document->importNode($fragment, true);
						$root->appendChild($node);
					}

					else if ($fragment instanceof DOMDocument && !is_null($fragment->documentElement)) {
						$node = $Document->importNode($fragment->documentElement, true);
						$root->appendChild($node);
					}
				}
			}

			if ($outputParameters->length() > 0) {
				foreach ($outputParameters as $p) {
					$Parameters->{$p->key} = $p->value;
				}
			}

			Profiler::end();
			Profiler::begin('Rendering parameters');

			####
			# Delegate: FrontendParamsPostResolve
			# Description: Access to the resolved param pool, including additional parameters provided by Data Source outputs
			# Global: Yes
			Extension::notify('FrontendParamsPostResolve', '/frontend/', array('params' => $Parameters));

			$element = $Document->createElement('parameters');
			$root->appendChild($element);

			foreach ($Parameters as $key => $parameter) {
				if (is_array($parameter->value) && count($parameter->value) > 1) {
					$p = $Document->createElement($key);
					$p->setAttribute('value', (string)$parameter);

					foreach ($parameter->value as $v) {
						$p->appendChild($Document->createElement('item', (string)$v));
					}

					$element->appendChild($p);
				}

				else {
					$element->appendChild($Document->createElement($key, (string)$parameter));
				}
			}

			$template = $this->template;

			Profiler::end();
			Profiler::begin('Executing template');

			####
			# Delegate: FrontendTemplatePreRender
			# Description: Access to the template source, before it is rendered.
			# Global: Yes
			Extension::notify(
				'FrontendTemplatePreRender', '/frontend/', array(
					'document'	=> $Document,
					'template'	=> &$template
				)
			);

			$this->template = $template;

			// When the XSLT executes, it uses the CWD as set here
			$cwd = getcwd();
			chdir(WORKSPACE);
			$output = XSLProc::transform(
				$Document, $this->template, XSLProc::DOC,
				$Parameters->toArray(), array()
			);
			chdir($cwd);

			if (XSLProc::hasErrors() && !isset($_GET['profiler'])) {
				throw new XSLProcException('Transformation Failed');
			}

			Profiler::end();

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
				$this->_current = View::loadFromPath($path);
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
