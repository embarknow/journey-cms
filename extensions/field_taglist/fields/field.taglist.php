<?php

	Class fieldTagList extends Field {

		public function __construct(){
			parent::__construct();
			$this->_name = __('Tag List');

			$this->{'suggestion-source-threshold'} = 2;
			$this->{'delimiter'} = ',';
			$this->{'suggestion-list-include-existing'} = false;

		}

		public function create(){
			return Symphony::Database()->query(sprintf('
				CREATE TABLE IF NOT EXISTS `tbl_data_%s_%s` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`entry_id` int(11) unsigned NOT NULL,
					`handle` varchar(255) default NULL,
					`value` varchar(255) default NULL,
					PRIMARY KEY  (`id`),
					KEY `entry_id` (`entry_id`),
					KEY `handle` (`handle`),
					KEY `value` (`value`)
				) ENGINE=MyISAM;',
				$this->section,
				$this->{'element-name'}
			));
		}

		public function requiresSQLGrouping() {
			return true;
		}

		public function allowDatasourceParamOutput(){
			return true;
		}

		function allowDatasourceOutputGrouping(){
			return true;
		}

		function canFilter(){
			return true;
		}

		public function canImport(){
			return true;
		}

		function canPrePopulate(){
			return true;
		}

		/*-------------------------------------------------------------------------
			Utilities:
		-------------------------------------------------------------------------*/

		public function prepopulateSource($wrapper) {

			$document = $wrapper->ownerDocument;

			$existing_tags = $this->findAllTags();

			if(is_array($existing_tags) && !empty($existing_tags)){
				$taglist = $document->createElement('ul');
				$taglist->setAttribute('class', 'tags');

				foreach($existing_tags as $tag) $taglist->appendChild($document->createElement('li', $tag));

				$wrapper->appendChild($taglist);
			}

		}

		public function findAllTags(){
			$values = $sources = array();

			if($this->{'suggestion-list-include-existing'} === true) {
				$sources[] = array(
					'section' => $this->section,
					'field_handle' => $this->{'element-name'}
				);
			}

			foreach($this->{'suggestion-list-source'} as $key => $handles) {
				list($section, $field) = $handles;
				$sources[] = array(
					'section' => $section,
					'field_handle' => $field
				);
			}

			foreach($sources as $source) {
				try {
					$result = Symphony::Database()->query("
							SELECT
								`value`
							FROM
								`tbl_data_%s_%s`
							WHERE
								`value` REGEXP '%s'
							GROUP BY
								`value`
							HAVING
								COUNT(`value`) >= %d
						", array(
							$source['section'],
							$source['field_handle'],
							(!empty($this->{'validator'})) ? rtrim(trim($this->{'validator'}, '/'), '/') : '.',
							$this->{'suggestion-source-threshold'}
						)
					);
				}

				catch (Exception $e) {
					continue;
				}

				if($result->valid()) $values = array_merge($values, $result->resultColumn('value'));
			}

			return array_unique($values);
		}

		public function __tagArrayToString(array $tags){
			return (!empty($tags)) ? implode($this->{'delimiter'} . ' ', $tags) : null;
		}

		public function applyValidationRules($data) {
			$rule = $this->{'validator'};

			return ($rule ? General::validateString($data, $rule) : true);
		}

		/*-------------------------------------------------------------------------
			Settings:
		-------------------------------------------------------------------------*/

		public function findDefaultSettings(array &$fields){
			if(!isset($fields['suggestion-list-source'])) $fields['suggestion-list-source'] = array();
		}

		public function displaySettingsPanel(SymphonyDOMElement $wrapper, MessageStack $errors = null) {
			parent::displaySettingsPanel($wrapper, $errors);

			$document = $wrapper->ownerDocument;

			$label = Widget::Label(__('Suggestion List'));

			$suggestion_list_source = $this->{'suggestion-list-source'};

			$options = array(
				array('existing', ($this->{'suggestion-list-include-existing'} === true || $this->{'suggestion-list-include-existing'} == 'yes'), __('Existing Values')),
			);

			foreach (new SectionIterator as $section) {
				if(!is_array($section->fields) || $section->handle == $this->section) continue;

				$fields = array();

				foreach($section->fields as $field) {
					if($field->canPrePopulate()) {
						$fields[] = array(
							$section->handle . '::' .$field->{'element-name'},
							(isset($this->{'suggestion-list-source'}["{$section->handle}::" . $field->{'element-name'}])),
							$field->{'publish-label'}
						);
					}
				}

				if(!empty($fields)) {
					$options[] = array(
						'label' => $section->name,
						'options' => $fields
					);
				}
			}

			$label->appendChild(Widget::Select('suggestion-list-source][', $options, array('multiple' => 'multiple')));
			$wrapper->appendChild($label);

			$group = $document->createElement('div');
			$group->setAttribute('class', 'group');

			// Suggestion threshold
			$input = Widget::Input('suggestion-source-threshold',$this->{'suggestion-source-threshold'});
			$label = Widget::Label(__('Minimum Tag Suggestion Threshold'), $input);
			$group->appendChild($label);

			// Custom delimiter
			$input = Widget::Input('delimiter', $this->{'delimiter'});
			$label = Widget::Label(__('Tag Delimiter'), $input);
			$group->appendChild($label);

			$wrapper->appendChild($group);

			// Validator
			$this->appendValidationSelect($wrapper, $this->validator, 'validator');

			$options_list = $document->createElement('ul');
			$options_list->setAttribute('class', 'options-list');

			$this->appendShowColumnCheckbox($options_list);
			$this->appendRequiredCheckbox($options_list);

			$wrapper->appendChild($options_list);
		}

		public function loadSettingsFromSimpleXMLObject(SimpleXMLElement $xml) {
			$suggestion_list_source = array();
			$suggestion_list_existing = false;

			if (isset($xml->{'suggestion-list-source'})) {
				if (isset($xml->{'suggestion-list-source'}->attributes()->{'include-existing'})) {
					$suggestion_list_existing =
						(string)$xml->{'suggestion-list-source'}
							->attributes()
							->{'include-existing'} == 'yes'
							? true
							: false;
				}

				foreach ($xml->{'suggestion-list-source'}->item as $item) {
					$key = sprintf('%s::%s', (string)$item->attributes()->section, (string)$item->attributes()->field);
					$suggestion_list_source[$key] = array((string)$item->attributes()->section, (string)$item->attributes()->field);
				}
			}

			unset($xml->{'suggestion-list-source'});

			foreach ($xml as $property_name => $property_value) {
				$data[(string)$property_name] = (string)$property_value;
			}

			$this->{'suggestion-list-source'} = $suggestion_list_source;
			$this->{'suggestion-list-include-existing'} = $suggestion_list_existing;

			// Set field GUID:
			if (isset($xml->attributes()->guid) and trim((string)$xml->attributes()->guid) != '') {
				$data['guid'] = (string)$xml->attributes()->guid;
			}

			$this->setPropertiesFromPostData($data);
		}

		public function setPropertiesFromPostData($data){
			if(isset($data['suggestion-list-source'])){

				$suggestion_list_source = array();

				if(!is_array($data['suggestion-list-source'])) $data['suggestion-list-source'] = (array)$data['suggestion-list-source'];

				foreach($data['suggestion-list-source'] as $item){

					if(preg_match('/::/', $item)){
						$suggestion_list_source[$item] = preg_split('/::/', $item, 2, PREG_SPLIT_NO_EMPTY);;
					}
					elseif($item == 'existing'){
						$this->{'suggestion-list-include-existing'} = true;
					}
				}

				$this->{'suggestion-list-source'} = $suggestion_list_source;
				unset($data['suggestion-list-source']);
			}

			return parent::setPropertiesFromPostData($data);
		}

		/*-------------------------------------------------------------------------
			Publish:
		-------------------------------------------------------------------------*/

		public function prepareTableValue($data, DOMElement $link=NULL){
			if(!is_array($data)){
				$data = array($data);
			}

			$values = array();
			foreach($data as $d){
				$values[] = $d->value;
			}

			return parent::prepareTableValue((object)array('value' => General::sanitize($this->__tagArrayToString($values))), $link);
		}

		public function displayPublishPanel(SymphonyDOMElement $wrapper, MessageStack $errors, Entry $entry = null, $data = null) {
			if(is_array($data)) {
				$values = array();
				foreach($data as $d) {
					$values[] = $d->value;
				}
				$data = (object)array('value' => $this->__tagArrayToString($values));
				unset($values);
			}

			if(!isset($data->value)) {
				$data->value = NULL;
			}

			$label = Widget::Label(
				(isset($this->{'publish-label'}) && strlen(trim($this->{'publish-label'})) > 0
					? $this->{'publish-label'}
					: $this->name)
			);

			$label->appendChild(
				Widget::Input('fields['.$this->{'element-name'}.']', $data->value)
			);

			if ($errors->valid()) {
				$label = Widget::wrapFormElementWithError($label, $errors->current()->message);
			}

			$wrapper->appendChild($label);

			if(!is_null($this->{'suggestion-list-source'})) $this->prepopulateSource($wrapper);
		}

		/*-------------------------------------------------------------------------
			Input:
		-------------------------------------------------------------------------*/

		public function processData($data, Entry $entry=NULL){
			$result = (object)array(
				'value' => null,
				'handle' => null
			);

			if(!is_null($data)){
				$result->value = $data;
				$result->handle = Lang::createHandle($data);
			}

			return $result;
		}

		public function validateData(MessageStack $errors, Entry $entry = null, $data = null) {
			$data = preg_split('/' . preg_quote($this->{'delimiter'}) . '/i', $data->value, -1, PREG_SPLIT_NO_EMPTY);
			$data = array_map('trim', $data);

			if(!is_array($data)) {
				$data = array($data);
			}

			$data = General::array_remove_duplicates($data, true);

			if ($this->{'required'} == 'yes' and empty($data)) {
				$errors->append(
					null, (object)array(
					 	'message' => __("'%s' is a required field.", array($this->{'publish-label'})),
						'code' => self::ERROR_MISSING
					)
				);

				return self::STATUS_ERROR;
			}

			foreach($data as $tag) {
				if ($this->{'required'} == 'yes' and strlen(trim($tag)) == 0) {
					$errors->append(
						null, (object)array(
						 	'message' => __("'%s' is a required field.", array($this->{'publish-label'})),
							'code' => self::ERROR_MISSING
						)
					);

					return self::STATUS_ERROR;
				}

				if (!isset($tag)) return self::STATUS_OK;

				if (!$this->applyValidationRules($tag)) {
					$errors->append(
						null, (object)array(
						 	'message' => __("'%s' contains invalid data. Please check the contents.", array($this->{'publish-label'})),
							'code' => self::ERROR_INVALID
						)
					);

					return self::STATUS_ERROR;
				}
			}

			return self::STATUS_OK;
		}

		public function saveData(MessageStack $errors, Entry $entry, $data = null) {
			// Since we are dealing with multiple
			// values, must purge the existing data first
			Symphony::Database()->delete(
				sprintf('tbl_data_%s_%s', $entry->section, $this->{'element-name'}),
				array($entry->id),
				"`entry_id` = %s"
			);

			$data = preg_split('/' . preg_quote($this->{'delimiter'}) . '/i', $data->value, -1, PREG_SPLIT_NO_EMPTY);
			$data = array_map('trim', $data);

			if(!is_array($data)) {
				$data = array($data);
			}

			$data = General::array_remove_duplicates($data, true);

			foreach($data as $tag) {
				$tag = $this->processData($tag, $entry);
				parent::saveData($errors, $entry, $tag);
			}

			return Field::STATUS_OK;
		}



		/*-------------------------------------------------------------------------
			Output:
		-------------------------------------------------------------------------*/

	    	public function toDoc() {
			$suggestion_list_source = NULL;
			if(isset($this->properties->{'suggestion-list-source'}) && is_array($this->properties->{'suggestion-list-source'})){
				$suggestion_list_source = $this->properties->{'suggestion-list-source'};
			}

			unset($this->properties->{'suggestion-list-source'});

			$include_existing = ($this->{'suggestion-list-include-existing'} == true ? 'yes' : 'no');
			unset($this->properties->{'suggestion-list-include-existing'});

			$doc = parent::toDoc();

			$this->properties->{'suggestion-list-include-existing'} = $include_existing;
			$this->properties->{'suggestion-list-source'} = $suggestion_list_source;

			if(!is_null($suggestion_list_source)){
				$element = $doc->createElement('suggestion-list-source');
				$element->setAttribute('include-existing', $include_existing);

				foreach($suggestion_list_source as $key => $value){
					list($section, $field) = $value;
					$item = $doc->createElement('item');
					$item->setAttributeArray(array('section' => $section, 'field' => $field));
					$element->appendChild($item);
				}
				$doc->documentElement->appendChild($element);
			}

			return $doc;
	    	}

	    	public function getParameterOutputValue($data, Entry $entry=NULL) {


	    		foreach($data as $tag){
	    			$tags[] = $tag->handle;
	    		}

			return $tags;
		}

		public function loadDataFromDatabase(Entry $entry, $expect_multiple = false){
			return parent::loadDataFromDatabase($entry, true);
		}

		public function appendFormattedElement(DOMElement $wrapper, $data, $encode=false, $mode=NULL, Entry $entry=NULL) {
			if (!is_array($data)) $data = array($data);

			if (empty($data) or is_null($data[0]->value)) return;

			$document = $wrapper->ownerDocument;

			$list = $document->createElement($this->{'element-name'});

			foreach($data as $tag){
				if($encode) $tag->value = General::sanitize($tag->value);

				$list->appendChild($document->createElement(
					'item', $tag->value, array(
						'handle' => $tag->handle
					)
				));
			}

			$wrapper->appendChild($list);
		}


		/*-------------------------------------------------------------------------
			Filtering:
		-------------------------------------------------------------------------*/

		public function displayDatasourceFilterPanel(SymphonyDOMElement $wrapper, $data=NULL, MessageStack $errors=NULL){
			parent::displayDatasourceFilterPanel($wrapper, $data, $errors);

			if (!is_null($this->{'suggestion-list-source'})) {
				$document = $wrapper->ownerDocument;
				$existing_options = $this->getToggleStates();

				$div = $document->createElement('div');
				$label = $document->xpath('.//label[last()]', $wrapper)->item(0);
				$label->wrapWith($div);

				$this->prepopulateSource($div);
			}
		}
	}

	return 'FieldTagList';