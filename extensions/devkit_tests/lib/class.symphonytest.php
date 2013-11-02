<?php

	/**
	 * @package lib
	 */

	require_once EXTENSIONS . '/devkit_tests/lib/simpletest/unit_tester.php';
	require_once EXTENSIONS . '/devkit_tests/lib/simpletest/mock_objects.php';
	require_once EXTENSIONS . '/devkit_tests/lib/simpletest/web_tester.php';
	require_once EXTENSIONS . '/devkit_tests/lib/simpletest/reporter.php';
	require_once EXTENSIONS . '/devkit_tests/lib/class.symphonytestiterator.php';
	//require_once EXTENSIONS . '/devkit_tests/lib/class.symphonytestpage.php';
	require_once EXTENSIONS . '/devkit_tests/lib/class.symphonytestreporter.php';

	/**
	 * The SymphonyTest class contains methods for loading test cases and handling
	 * or extracting information for test cases.
	 */
	class SymphonyTest {
		static public $instances;

		/**
		 * Does a test case exist?
		 * @param string $handle The handle of the test case.
		 * @access public
		 * @static
		 */
		static public function exists($handle) {
			$iterator = new SymphonyTestIterator();

			return $iterator->hasFileWithHandle($handle);
		}

		/**
		 * Load a test case.
		 * @param string $path The full path to the test case.
		 * @access public
		 * @static
		 */
		static public function load($path) {
			if (!isset(self::$instances)) {
				self::$instances = array();
			}

			if (file_exists($path)) {
				$handle = self::findHandleFromPath($path);
				$class = self::findClassNameFromPath($path);
			}

			else {
				$handle = $path;
				$path = self::findPathFromHandle($path);
				$class = self::findClassNameFromPath($path);
			}

			if (!in_array($class, self::$instances)) {
				require_once $path;

				$instance = new $class;
				$instance->handle = $handle;

				if (
					($instance instanceof SimpleTestCase) === false
					&& ($instance instanceof TestSuite) === false
				) {
					throw new Exception('Unit test class must implement interface SimpleTestCase.');
				}

				self::$instances[$class] = $instance;
			}

			return self::$instances[$class];
		}

		/**
		 * Extract the class name from a path.
		 * @param string $path A valid test case path.
		 * @access public
		 * @static
		 */
		static public function findClassNameFromPath($path) {
			$handle = self::findHandleFromPath($path);
			$class = ucwords(str_replace('-', ' ', Lang::createHandle($handle)));
			$class = 'SymphonyTest' . str_replace(' ', null, $class);

			return $class;
		}

		/**
		 * Extract the handle from a path.
		 * @param string $path A valid test case path.
		 * @access public
		 * @static
		 */
		static public function findHandleFromPath($path) {
			return preg_replace('%^test\.|\.php$%', null, basename($path));
		}

		/**
		 * Find the first test case that has the supplied handle.
		 * @param string $path A valid test case handle.
		 * @access public
		 * @static
		 */
		static public function findPathFromHandle($handle) {
			foreach (new SymphonyTestIterator() as $filter) {
				if (self::findHandleFromPath($filter) == $handle) return $filter;
			}

			return null;
		}

		/**
		 * Get information about a test case using reflection.
		 * @param SimpleTestCase|TestSuite $object A test case object.
		 * @access public
		 * @static
		 */
		static public function readInformation($object) {
			$extension_dir = EXTENSIONS . '/devkit_tests';
			$reflection = new ReflectionObject($object);
			$filename = $reflection->getFileName();
			$comment = new ReflectionComment($reflection);

			$info = (object)array(
				'name'			=> $reflection->getName(),
				'description'	=> null,
				'in-extension'	=> (strpos($filename, realpath(EXTENSIONS) . '/') === 0),
				'in-symphony'	=> (
					strpos($filename, SYMPHONY . '/') === 0
					|| strpos($filename, $extension_dir . '/core-tests/') === 0
				),
				'in-workspace'	=> (strpos($filename, realpath(WORKSPACE) . '/') === 0)
			);

			if ($info->{'in-extension'}) {
				$info->extension = basename(dirname(dirname($filename)));
			}

			// Extract natural name:
			if ($comment->hasTitle()) {
				$info->name = $comment->getTitle();
			}

			// Extract description:
			if ($comment->hasDescription()) {
				$info->description = $comment->getDescription();
			}

			return $info;
		}
	}