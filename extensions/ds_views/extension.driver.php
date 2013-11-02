<?php

	class Extension_DS_Views implements ExtensionInterface, ExtensionWithIncludesInterface {
		public function about() {
			return (object)array(
				'name'			=> 'Views DataSource',
				'version'		=> '1.0.0',
				'release-date'	=> '2010-02-26',
				'type'			=> array(
					'Data Source', 'Core'
				),
				'author'		=> (object)array(
					'name'			=> 'R&B Creative',
					'website'		=> 'http://www.randb.com.au/',
					'email'			=> 'team@symphony-cms.com'
				),
				'provides'		=> array(
					'datasource_template'
				),
				'description'	=> 'Create data sources from view navigation data.'
			);
		}

		public function includeFiles() {
			require_once __DIR__ . '/lib/class.datasource.php';
		}

	/*-------------------------------------------------------------------------
		DataSources:
	-------------------------------------------------------------------------*/

		public function getDataSourceTypes() {
			return array(
				(object)array(
					'class'		=> 'ViewsDataSource',
					'name'		=> __('Views')
				)
			);
		}
	}

	return 'Extension_DS_Views';