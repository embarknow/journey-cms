<?php

	class Extension_DevKit_Debug implements ExtensionInterface, ExtensionWithIncludesInterface {
	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/

		public static $view = null;
		public static $class = null;

		public function about() {
			return (object)array(
				'name'			=> 'Debug DevKit',
				'version'		=> '2.0',
				'release-date'	=> '2010-04-28',
				'author'		=> (object)array(
					'name'			=> 'R&B Creative',
					'website'		=> 'http://www.randb.com.au/',
					'email'			=> 'me@rowanlewis.com'
				),
				'provides'		=> array(
					'devkit'
				)
			);
		}

		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '/frontend/',
					'delegate'	=> 'ExecuteLateDevKit',
					'callback'	=> 'executeLateDevKit'
				),
				array(
					'page'		=> '/frontend/',
					'delegate'	=> 'DevKiAppendtMenuItem',
					'callback'	=> 'appendDevKitMenuItem'
				)
			);
		}

		public function includeFiles() {
			self::$class = require_once __DIR__ . '/views/debug.php';
		}

		public function executeLateDevKit($context) {
			if (
				isset($_GET['debug'])
				&& class_exists('Frontend')
				&& Frontend::instance()->isLoggedIn()
			) {
				$view = new self::$class(Frontend::loadedView());

				$context['output'] = $view->render(
					Frontend::Parameters(),
					Frontend::Document(),
					$context['output']
				);
			}
		}

		public function appendDevKitMenuItem($context) {
			$wrapper = $context['wrapper'];
			$document = $wrapper->ownerDocument;

			$item = $document->createElement('item');
			$item->setAttribute('name', __('Debug'));
			$item->setAttribute('handle', 'debug');
			$item->setAttribute('active', (
				isset($_GET['debug'])
					? 'yes' : 'no'
			));

			if ($wrapper->hasChildNodes()) {
				$wrapper->insertBefore($item, $wrapper->firstChild);
			}

			else {
				$wrapper->appendChild($item);
			}
		}
	}

	return 'Extension_DevKit_Debug';
