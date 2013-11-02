<?php

	/**
	 * Utility functions specific to the Symphony boot process, namespaced
	 * as such to prevent conflicts with existing/other functions.
	 */
	namespace boot {
		use Profiler;
		use Extension;
		use Exception;

		function launch() {
			// Load the renderer:
			$handle = (
				isset($_GET['symphony-renderer'])
					? $_GET['symphony-renderer']
					: 'frontend'
			);
			$path = null;

			// Resolve a filesystem reference:
			if (file_exists(realpath($handle))) {
				$path = realpath($handle);

				// Ensure the renderer is child of DOCROOT. Closes potential
				// security hole
				$docroot = realpath(DOCROOT . '/../');

				if (substr($path, 0, strlen($docroot)) != $docroot) {
					$path = null;
				}
			}

			else if (file_exists(LIB . "/class.{$handle}.php")) {
				$path = LIB . "/class.{$handle}.php";
			}

			if (isset($path) === false) {
				throw new Exception("Invalid Symphony renderer handle specified. {$handle} given.");
			}

			Profiler::begin('Begin Symphony execution');

			$class = require_once $path;
			$renderer = call_user_func("\\{$class}::instance");

			Profiler::store('class', $class, 'system/class');
			Profiler::store('location', $path, 'system/resource action/executed');

			return $renderer;
		}

		function devkit($delegate, $output = null) {
			if (php_sapi_name() === 'cli') return;

			Extension::notify($delegate, '/frontend/', array(
				'output'	=> &$output
			));

			if (isset($output)) output($output);
		}

		function output($output) {
			header(sprintf('Content-Length: %d', strlen($output)));
			echo $output;
			exit;
		}
	}

	/**
	 * Prepare the environment for Symphony, allow it to be booted by calling render.
	 */
	namespace {
		function render() {
			// Begin profiling:
			if (isset($_GET['profiler'])) Profiler::enable();

			$output = Controller::instance()->renderView();

			// Stop profiler:
			Profiler::end();
			Profiler::disable();

			// Allow Devkits to take control over the renderers output:
			boot\devkit('ExecuteLateDevKit', $output);
			boot\output($output);
		}

		// Show most errors so that they can be caught and handled:
		error_reporting(
			PHP_VERSION_ID >= 50400
				? E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE
				: E_ALL & ~E_NOTICE
		);

		// Do the magic and clear caches:
		if (isset($_GET['clear-apc-cache'])) {
			apc_clear_cache('user');
			apc_clear_cache('opcode');
			apc_clear_cache();
			ExtensionIterator::clearCachedFiles();
			SectionIterator::clearCachedFiles();
		}

		require_once DOCROOT . '/symphony/lib/class.profiler.php';
		require_once DOCROOT . '/symphony/lib/include.utilities.php';
		require_once DOCROOT . '/symphony/defines.php';
		require_once LIB . '/class.controller.php';
	}