<?php

	/**
	 * @package symphony_tests
	 */

	/**
	 * A SimpleTest interface for Symphony.
	 */
	class ExtensionDevkitTests implements ExtensionInterface {
		public static $view;
		public static $class;

		public function about() {
			return (object)array(
				'name'			=> 'Tests Devkit',
				'version'		=> '0.1',
				'release-date'	=> '2013-05-07',
				'author'		=> (object)array(
					'name'			=> 'Rowan Lewis',
					'website'		=> 'http://nbsp.io',
					'email'			=> 'rl@nbsp.io'
				),
				'type'			=> array(
					'Core'
				)
			);
		}

		/**
		 * Listen for these delegates.
		 */
		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '/frontend/',
					'delegate'	=> 'ExecuteEarlyDevKit',
					'callback'	=> 'executeEarlyDevKit'
				)
			);
		}

		/**
		 * Get the current extension directory.
		 */
		public function getExtensionDir() {
			return dirname(__FILE__);
		}

		public function executeEarlyDevKit($context) {
			if (isset($_GET['tests'])) {
				$class = require_once __DIR__ . '/views/tests.php';
				$view = new $class(new View());

				$context['output'] = $view->render(
					new Register(),
					Frontend::Document(),
					Frontend::Headers()
				);
			}
		}
	}

	return ExtensionDevkitTests;