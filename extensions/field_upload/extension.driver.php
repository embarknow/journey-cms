<?php

	class Extension_Field_Upload implements ExtensionInterface {
	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/

		public function about() {
			return (object)array(
				'name'			=> 'Upload',
				'version'		=> '2.0.0',
				'release-date'	=> '2010-04-20',
				'author'		=> (object)array(
					'name'			=> 'R&B Creative',
					'website'		=> 'http://www.randb.com.au/',
					'email'			=> 'me@rowanlewis.com'
				),
				'description'	=> 'An upload field that allows features to be plugged in.',
				'type'			=> array(
					'Field', 'Core'
				),
			);
		}

		public function getFieldTypes() {
			require_once __DIR__ . '/fields/field.upload.php';

			return array(
				(object)array(
					'class'		=> 'FieldUpload',
					'name'		=> __('Upload')
				)
			);
		}

	/*-------------------------------------------------------------------------
		Utilites:
	-------------------------------------------------------------------------*/

		protected $addedHeaders = false;

		public function addHeaders() {
			$page = Symphony::Parent()->Page;

			if (!$this->addedHeaders) {
				$page->insertNodeIntoHead($page->createStylesheetElement(URL . '/extensions/field_upload/assets/publish.css'));
				$page->insertNodeIntoHead($page->createScriptElement(URL . '/extensions/field_upload/assets/publish.js'));

				$this->addedHeaders = true;
			}
		}
	}

	return 'Extension_Field_Upload';