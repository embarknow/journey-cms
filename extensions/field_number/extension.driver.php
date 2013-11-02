<?php

	Class Extension_Field_Number implements ExtensionInterface{

		public function about(){
			return (object)array(
				'name' 			=> 'Number',
				'version' 		=> '2.0.0',
				'release-date' 	=> '2010-06-18',
				'author' 		=> (object)array(
					'name'			=> 'R&B Creative',
					'website'		=> 'http://www.randb.com.au/',
					'email'			=> 'team@symphony-cms.com'
				),
				'type'			=> array(
					'Field'
				)
		 	);
		}

		public function getFieldTypes() {
			require_once __DIR__ . '/fields/field.number.php';

			return array(
				(object)array(
					'class'		=> 'FieldNumber',
					'name'		=> __('Number')
				)
			);
		}
	}

	return 'Extension_Field_Number';