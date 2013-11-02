<?php

	Class Extension_Field_Link implements ExtensionInterface{

		public function about(){
			return (object)array('name' => 'Link',
						 'version' => '2.0.0',
						 'release-date' => '2010-02-02',
						 'author' => (object)array('name' => 'R&B Creative',
										   'website' => 'http://www.symphony-cms.com',
										   'email' => 'team@symphony-cms.com'),
				'type' => array(
					'Field', 'Core'
				)
			);
		}

		public function getFieldTypes() {
			require_once __DIR__ . '/fields/field.link.php';
			require_once __DIR__ . '/fields/field.linked-entries.php';

			return array(
				(object)array(
					'class'		=> 'FieldLink',
					'name'		=> __('Link')
				),
				(object)array(
					'class'		=> 'FieldLinked_Entries',
					'name'		=> __('Linked Entries')
				)
			);
		}
	}

	return 'Extension_Field_Link';