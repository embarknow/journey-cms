<?php

use Embark\CMS\SystemDateTime;
use Embark\CMS\Actors\Controller as ActorController;
use Embark\CMS\Structures\ParameterPool as Context;
use Embark\CMS\Configuration\Controller as Configuration;

	// Class DataSourceException extends Exception {}

	Class DataSourceFilterIterator extends FilterIterator{
		public function __construct($path)
		{
			parent::__construct(new DirectoryIterator($path));
		}

		public function accept()
		{
			return (
				false === $this->isDir()
				&& preg_match('/^.+\.xml$/i', $this->getFilename())
			);
		}
	}

	Class DataSourceIterator implements Iterator{
		private static $datasources;
		private $position;

		public function __construct(){
			$this->position = 0;

			if (!empty(self::$datasources)) return;

			self::clearCachedFiles();

			foreach (new DataSourceFilterIterator(DATASOURCES) as $file) {
				self::$datasources[] = $file->getPathname();
			}

			$extensions = new ExtensionQuery();
			$extensions->setFilters(array(
				ExtensionQuery::STATUS =>	Extension::STATUS_ENABLED
			));

			foreach ($extensions as $extension) {
				if (is_dir($extension->path . '/data-sources') === false) continue;

				foreach (new DataSourceFilterIterator($extension->path . '/data-sources') as $file) {
					self::$datasources[] = $file->getPathname();
				}
			}
		}

		public static function clearCachedFiles() {
			self::$datasources = array();
		}

		public function length(){
			return count(self::$datasources);
		}

		public function rewind(){
			$this->position = 0;
		}

		public function current(){
			return self::$datasources[$this->position]; //Datasource::loadFromPath($this->datasources[$this->position]);
		}

		public function key(){
			return $this->position;
		}

		public function next(){
			++$this->position;
		}

		public function valid(){
			return isset(self::$datasources[$this->position]);
		}
	}



	##Interface for datasouce objects
	Abstract Class DataSource{

		const FILTER_AND = 1;
		const FILTER_OR = 2;

		protected $_about;
		protected $_parameters;

		protected static $_loaded;

		// Abstract function
		abstract public function render(Context $ParameterOutput);

		public static function getHandleFromFilename($filename){
			return preg_replace('/(.(php|xml)$|\/.*\/)/i', NULL, $filename);
		}

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

		public static function load($pathname)
		{
			if (!is_array(self::$_loaded)) {
				self::$_loaded = array();
			}

			if (!is_file($pathname)) {
		        throw new DataSourceException(
					__('Could not find Data Source <code>%s</code>. If the Data Source was provided by an Extension, ensure that it is installed, and enabled.', array(basename($pathname)))
				);
			}

			// Compensate for symlinking:
			$pathname = realpath($pathname);

			if (!isset(self::$_loaded[$pathname])) {
				$data = new DOMDocument();
				$data->load($pathname);

				self::$_loaded[$pathname] = ActorController::fromXML($data);
			}

			$obj = self::$_loaded[$pathname];

			return $obj;
		}

		public static function loadFromHandle($name){
			return self::load(self::__find($name) . "/{$name}.xml");
		}

		protected static function __find($name){

		    if (is_file(DATASOURCES . "/{$name}.xml")) {
		    	return DATASOURCES;
		    }

		    else {
				$extensions = new ExtensionQuery();
				$extensions->setFilters(array(
					ExtensionQuery::STATUS =>	Extension::STATUS_ENABLED
				));

				foreach ($extensions as $extension) {
					if (is_file(EXTENSIONS . "/{$extension->handle}/data-sources/{$name}.xml")) {
						return EXTENSIONS . "/{$extension->handle}/data-sources";
					}
				}
	    	}

		    return false;
	    }

		## This function is required in order to edit it in the data source editor page.
		## Overload this function, and return false if you do not want the Data Source editor
		## loading your Data Source
		public function allowEditorToParse(){
			return true;
		}

		public function getExtension(){
			return NULL;
		}

		public function getTemplate(){
			return NULL;
		}

		public function prepareSourceColumnValue() {
			return Widget::TableData(__('None'), array('class' => 'inactive'));
		}

		public function __get($name){
			if($name == 'handle'){
				return Lang::createFilename($this->about()->name);
			}
		}

		public function save(MessageStack $errors){
			$editing = (isset($this->parameters()->{'root-element'}))
						? $this->parameters()->{'root-element'}
						: false;

			// About info:
			if (!isset($this->about()->name) || empty($this->about()->name)) {
				$errors->append('about::name', __('This is a required field'));
			}

			try {
				$existing = self::loadFromHandle($this->handle);
			}
			catch (DataSourceException $e) {
				//	Datasource not found, continue!
			}

			if($existing instanceof Datasource && $editing != $this->handle) {
				throw new DataSourceException(__('A Datasource with the name <code>%s</code> already exists', array($this->about()->name)));
			}

			// Save type:
			if ($errors->length() <= 0) {
				$user = Symphony::User();

				if (!file_exists($this->getTemplate())) {
					$errors->append('write', __("Unable to find Data Source Type template '%s'.", array($this->getTemplate())));
					throw new DataSourceException(__("Unable to find Data Source Type template '%s'.", array($this->getTemplate())));
				}

				$this->parameters()->{'root-element'} = $this->handle;
				$classname = Lang::createHandle(ucwords($this->about()->name), '_', false, true, array('/[^a-zA-Z0-9_\x7f-\xff]/' => NULL), true);
				$pathname = DATASOURCES . "/" . $this->handle . ".php";
				$date = new SystemDateTime();

				$data = array(
					$classname,
					// About info:
					var_export($this->about()->name, true),
					var_export($user->getFullName(), true),
					var_export(URL, true),
					var_export($user->email, true),
					var_export('1.0', true),
					var_export($date->format(DateTime::W3C), true),
				);

				foreach ($this->parameters() as $value) {
					$data[] = trim(General::var_export($value, true, (is_array($value) ? 5 : 0)));
				}

				if(General::writeFile(
					$pathname,
					vsprintf(file_get_contents($this->getTemplate()), $data),
					Configuration::read('main')['system']['file-write-mode']
				)){
					if($editing !== false && $editing != $this->handle) General::deleteFile(DATASOURCES . '/' . $editing . '.php');

					return $pathname;
				}

				$errors->append('write', __('Failed to write datasource "%s" to disk.', array($filename)));
			}

			throw new DataSourceException('Errors were encountered whilst attempting to save.');
		}

		public function delete($datasource){
			/*
				TODO:
				Upon deletion of the event, views need to be updated to remove
				it's associated with the event
			*/
			if(!$datasource instanceof DataSource) {
				$datasource = Datasource::loadFromHandle($datasource);
			}

			$handle = $datasource->handle;

			if(!$datasource->allowEditorToParse()) {
				throw new DataSourceException(__('Datasource cannot be deleted, the Editor does not have permission.'));
			}

			return General::deleteFile(DATASOURCES . "/{$handle}.php");
		}

		public function emptyXMLSet(DOMElement $root){
			if(is_null($root)) {
				throw new DataSourceException('No valid DOMDocument present');
			}
			else {
				$root->appendChild(
					$root->ownerDocument->createElement('error', __('No records found.'))
				);
			}
		}

		public static function determineFilterType($string){
		 	return preg_match('/\s+\+\s+/', $string) ? DataSource::FILTER_AND : DataSource::FILTER_OR;
		}

		public static function prepareFilterValue($value, Context $ParameterOutput=NULL, &$filterOperationType=DataSource::FILTER_OR){

			if(strlen(trim($value)) == 0) return NULL;

			if(is_array($value)) {
				foreach($value as $k => $v) {
					$value[$k] = self::prepareFilterValue($v, $ParameterOutput, $filterOperationType);
				}
			}
			else {
				$value = self::replaceParametersInString($value, $ParameterOutput);

				$filterOperationType = self::determineFilterType($value);
				$pattern = ($filterOperationType == DataSource::FILTER_AND ? '\s+\+\s+' : '(?<!\\\\),');

				// This is where the filter value is split by commas or + symbol, denoting
				// this as an OR or AND operation. Comma's have already been escaped
				$value = preg_split("/{$pattern}\s*/", $value, -1, PREG_SPLIT_NO_EMPTY);
				$value = array_map('trim', $value);

				// Remove the escapes on commas
				$value = array_map(array('General', 'removeEscapedCommas'), $value);

				// Pre-escape the filter values. TODO: Should this be here?
				$value = array_map(array(Symphony::Database(), 'escape'), $value);
			}

			return $value;
		}

		/*
		**	Given a string that may contain params in form of {$param}
		**	resolve the tokens with their values
		**
		**	This checks both the Frontend Parameters and Datasource
		**	Registers.
		*/
		public static function replaceParametersInString($string, Context $DataSourceParameterOutput = null) {
			if(strlen(trim($string)) == 0) return null;

			if(preg_match_all('@{([^}]+)}@i', $string, $matches, PREG_SET_ORDER)){
				foreach($matches as $match){
					list($source, $cleaned) = $match;

					$replacement = NULL;

					$bits = preg_split('/:/', $cleaned, -1, PREG_SPLIT_NO_EMPTY);

					foreach($bits as $param) {
						if($param{0} != '$') {
							$replacement = $param;
							break;
						}

						$param = trim($param, '$');

						$replacement = self::resolveParameter($param, $DataSourceParameterOutput);

						if(is_array($replacement)){
							$replacement = array_map(array('General', 'escapeCommas'), $replacement);
							if(count($replacement) > 1) $replacement = implode(',', $replacement);
							else $replacement = end($replacement);
						}

						if(!is_null($replacement)) break;
					}

					$string = str_replace($source, $replacement, $string);
				}
			}

			return $string;
		}

		public static function resolveParameter($param, Context $DataSourceParameterOutput = null) {
			//	TODO: Allow resolveParamter to check the stack, ie: $ds-blog-tag:$ds-blog-id
			$param = trim($param, '$');

			if(isset(Frontend::Parameters()->{$param})) return Frontend::Parameters()->{$param}->value;

			if(isset($DataSourceParameterOutput->{$param})) return $DataSourceParameterOutput->{$param}->value;

			return null;
		}
	}
