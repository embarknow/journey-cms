<?php

	class Extension_DevKit_Profiler implements ExtensionInterface, ExtensionWithIncludesInterface {
	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/

		public static $view;
		public static $class;

		public function about() {
			return (object)array(
				'name'			=> 'Profiler DevKit',
				'version'		=> '2.0',
				'release-date'	=> '2011-11-10',
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
			self::$class = require_once __DIR__ . '/views/profiler.php';
		}

		public function executeLateDevKit($context) {
			if (
				isset($_GET['profiler'])
				&& class_exists('Frontend')
				&& Frontend::instance()->isLoggedIn()
			) {
				$view = new self::$class(Frontend::loadedView());

				$context['output'] = $view->render(
					Frontend::Parameters(),
					Frontend::Document(),
					Frontend::Headers()
				);
			}
		}

		public function appendDevKitMenuItem($context) {
			$wrapper = $context['wrapper'];
			$document = $wrapper->ownerDocument;

			$item = $document->createElement('item');
			$item->setAttribute('name', __('Profiler'));
			$item->setAttribute('handle', 'profiler');
			$item->setAttribute('active', (
				isset($_GET['profiler'])
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

	return 'Extension_DevKit_Profiler';