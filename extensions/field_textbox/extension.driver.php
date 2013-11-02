<?php

	class Extension_Field_TextBox implements ExtensionInterface {
		public function about() {
			return (object)array(
				'name'			=> 'Text Box',
				'version'		=> '2.0.17',
				'release-date'	=> '2010-04-21',
				'author'		=> (object)array(
					'name'			=> 'R&B Creative',
					'website'		=> 'http://www.randb.com.au/',
					'email'			=> 'me@rowanlewis.com'
				),
				'description' => 'An enhanced text input field.',
				'type'			=> array(
					'Field', 'Core'
				),
			);
		}

		public function getFieldTypes() {
			require_once __DIR__ . '/fields/field.textbox.php';

			return array(
				(object)array(
					'class'		=> 'FieldTextBox',
					'name'		=> __('Text Box')
				)
			);
		}

	/*-------------------------------------------------------------------------
		Utilites:
	-------------------------------------------------------------------------*/

		protected static $addedPublishHeaders = false;
		protected static $addedSettingsHeaders = false;
		protected static $addedFilteringHeaders = false;

		public function addPublishHeaders($page) {
			if ($page and !self::$addedPublishHeaders) {
				$page->insertNodeIntoHead($page->createStylesheetElement(URL . '/extensions/field_textbox/assets/publish.css'));
				$page->insertNodeIntoHead($page->createScriptElement(URL . '/extensions/field_textbox/assets/publish.js'));

				self::$addedPublishHeaders = true;
			}
		}

		public function addSettingsHeaders($page) {
			if ($page and !self::$addedSettingsHeaders) {
				$page->insertNodeIntoHead($page->createStylesheetElement(URL . '/extensions/field_textbox/assets/settings.css'));

				self::$addedSettingsHeaders = true;
			}
		}

		public function addFilteringHeaders($page) {
			if ($page and !self::$addedFilteringHeaders) {
				$page->insertNodeIntoHead($page->createStylesheetElement(URL . '/extensions/field_textbox/assets/filtering.css'));
				$page->insertNodeIntoHead($page->createScriptElement(URL . '/extensions/field_textbox/assets/interface.js'));
				$page->insertNodeIntoHead($page->createScriptElement(URL . '/extensions/field_textbox/assets/filtering.js'));

				self::$addedFilteringHeaders = true;
			}
		}
	}

	return 'Extension_Field_TextBox';
