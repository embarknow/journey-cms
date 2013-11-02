<?php

	class Extension_DS_DynamicXML implements ExtensionInterface {
		public function about() {
			return (object)array(
				'name'			=> 'Dynamic XML DataSource',
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
				'description'	=> 'Create data sources from XML fetched over HTTP or FTP.'
			);
		}

	/*-------------------------------------------------------------------------
		DataSources:
	-------------------------------------------------------------------------*/

		public function getDataSourceTypes() {
			require_once __DIR__ . '/lib/class.datasource.php';

			return array(
				(object)array(
					'class'		=> 'DynamicXMLDataSource',
					'name'		=> __('Dynamic XML')
				)
			);
		}
	}

	return 'Extension_DS_DynamicXML';
