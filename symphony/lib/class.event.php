<?php

use Embark\CMS\Structures\ParameterPool as Context;

use Embark\CMS\SystemDateTime;

	Class EventException extends Exception {}

	Class EventFilterIterator extends FilterIterator{
		public function __construct($path){
			parent::__construct(new DirectoryIterator($path));
		}

		public function accept(){
			if($this->isDir() == false && preg_match('/^.+\.php$/i', $this->getFilename())){
				return true;
			}
			return false;
		}
	}

	Class EventIterator implements Iterator{
		private static $events;
		private $position;

		public function __construct(){
			$this->position = 0;

			if (!empty(self::$events)) return;

			self::clearCachedFiles();

			foreach (new EventFilterIterator(EVENTS) as $file) {
				self::$events[] = $file->getPathname();
			}

			$extensions = new ExtensionQuery();
			$extensions->setFilters(array(
				ExtensionQuery::STATUS =>	Extension::STATUS_ENABLED
			));

			foreach ($extensions as $extension) {
				if (is_dir($extension->path . '/events') === false) continue;

				foreach (new EventFilterIterator($extension->path . '/events') as $file) {
					self::$events[] = $file->getPathname();
				}
			}
		}

		public static function clearCachedFiles() {
			self::$events = array();
		}

		public function length(){
			return count(self::$events);
		}

		public function rewind(){
			$this->position = 0;
		}

		public function current(){
			return self::$events[$this->position]; //Datasource::loadFromPath($this->events[$this->position]);
		}

		public function key(){
			return $this->position;
		}

		public function next(){
			++$this->position;
		}

		public function valid(){
			return isset(self::$events[$this->position]);
		}
	}

	Abstract Class Event{

		const PRIORITY_HIGH = 3;
		const PRIORITY_NORMAL = 2;
		const PRIORITY_LOW = 1;

		const ERROR_MISSING_OR_INVALID_FIELDS = 4;
		const ERROR_FAILED_TO_WRITE = 5;

		protected static $_loaded;

		abstract public function canTrigger(array $data);
		abstract public function trigger(Context $ParameterOutput, array $data);

		protected $_about;
		protected $_parameters;

		public function &about(){
			return $this->_about;
		}

		public function &parameters(StdClass $data = null) {
			if (isset($this->_parameters) === false) {
				$this->_parameters = new StdClass();
			}

			if (isset($data)) foreach ($data as $key => $value) {
				$this->_parameters->{$key} = $value;
			}

			return $this->_parameters;
		}

		public static function load($pathname){
			if(!is_array(self::$_loaded)){
				self::$_loaded = array();
			}

			if(!is_file($pathname)){
		        throw new EventException(
					__('Could not find Event <code>%s</code>. If the Event was provided by an Extension, ensure that it is installed, and enabled.', array(basename($pathname)))
				);
			}

			if(!isset(self::$_loaded[$pathname])){
				self::$_loaded[$pathname] = require($pathname);
			}

			$obj = new self::$_loaded[$pathname];
			$obj->parameters()->pathname = $pathname;

			return $obj;

		}

		public static function loadFromHandle($name){
			return self::load(self::__find($name) . "/{$name}.php");
		}

		protected static function __find($name){

		    if (is_file(EVENTS . "/{$name}.php")) {
		    	return EVENTS;
		    }

		    else {
				$extensions = new ExtensionQuery();
				$extensions->setFilters(array(
					ExtensionQuery::STATUS =>	Extension::STATUS_ENABLED
				));

				foreach ($extensions as $extension) {
					if (is_file(EXTENSIONS . "/{$extension->handle}/events/{$name}.php")) {
						return EXTENSIONS . "/{$extension->handle}/events";
					}
				}
	    	}

		    return false;
	    }

		public function __get($name){
			if($name == 'handle'){
				return Lang::createFilename($this->about()->name);
			}
		}

		public function getExtension(){
			return NULL;
		}

		public function getTemplate(){
			return NULL;
		}

		public function prepareDestinationColumnValue() {
			return Widget::TableData(__('None'), array('class' => 'inactive'));
		}

		public function save(MessageStack $errors){
			$editing = (isset($this->parameters()->{'root-element'}))
						? $this->parameters()->{'root-element'}
						: false;

			if (!isset($this->about()->name) || empty($this->about()->name)) {
				$errors->append('about::name', __('This is a required field'));
			}

			try {
				$existing = self::loadFromHandle($this->handle);
			}
			catch(EventException $e) {
				//	Event not found, continue
			}

			if($existing instanceof Event && $editing != $this->handle) {
				throw new EventException(__('An Event with the name <code>%s</code> already exists.', array($this->about()->name)));
			}

			// Save type:
			if ($errors->length() <= 0) {
				$user = Symphony::User();

				if (!file_exists($this->getTemplate())) {
					$errors->append('write', __("Unable to find Event Type template '%s'.", array($this->getTemplate())));
					throw new EventException(__("Unable to find Event Type template '%s'.", array($this->getTemplate())));
				}

				$this->parameters()->{'root-element'} = $this->handle;
				$classname = Lang::createHandle(ucwords($this->about()->name), '_', false, true, array('/[^a-zA-Z0-9_\x7f-\xff]/' => NULL), true);
				$pathname = EVENTS . "/" . $this->handle . ".php";

				$data = array(
					$classname,
					// About info:
					var_export($this->about()->name, true),
					var_export($user->getFullName(), true),
					var_export(URL, true),
					var_export($user->email, true),
					var_export('1.0', true),
					var_export((new SystemDateTime)->format(DateTime::W3C), true),
				);

				foreach ($this->parameters() as $value) {
					$data[] = trim(General::var_export($value, true, (is_array($value) ? 5 : 0)));
				}

				if(General::writeFile(
					$pathname,
					vsprintf(file_get_contents($this->getTemplate()), $data),
					Symphony::Configuration()->main()->system->{'file-write-mode'}
				)){
					if($editing !== false && $editing != $this->handle) General::deleteFile(EVENTS . '/' . $editing . '.php');

					return $pathname;
				}

				$errors->append('write', __('Failed to write event "%s" to disk.', array($filename)));
			}

			throw new EventException(__('Event could not be saved. Validation failed.'), self::ERROR_MISSING_OR_INVALID_FIELDS);
		}

		public function delete($event){
			/*
				TODO:
				Upon deletion of the event, views need to be updated to remove
				it's associated with the event
			*/
			if(!$event instanceof Event) {
				$event = Event::loadFromHandle($event);
			}

			$handle = $event->handle;

			if(!$event->allowEditorToParse()) {
				throw new EventException(__('Event cannot be deleted, the Editor does not have permission.'));
			}

			return General::deleteFile(EVENTS . "/{$handle}.php");
		}

		## This function is required in order to edit it in the event editor page.
		## Do not overload this function if you are creating a custom event. It is only
		## used by the event editor
		public function allowEditorToParse(){
			return false;
		}

		public function priority(){
			return self::PRIORITY_NORMAL;
		}

		public static function getHandleFromFilename($filename){
			return preg_replace('/(.php$|\/.*\/)/i', NULL, $filename);
		}
	}

