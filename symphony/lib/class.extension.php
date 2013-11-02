<?php

	interface ExtensionInterface {

	}

	interface ExtensionWithIncludesInterface {
		public function includeFiles();
	}

	interface ExtensionWithSectionsInterface {
		public function includeSections();
	}

	class ExtensionException extends Exception {

	}

	abstract class Extension {
		static private $loaded_extensions;
		static private $extensions_class_to_path;
		static private $extension_configuration;
		static private $extensions;
		static private $extension_statuses;
		static private $database;

		const STATUS_ENABLED = 'enabled';
		const STATUS_DISABLED = 'disabled';
		const STATUS_NOT_INSTALLED = 'not-installed';
		const STATUS_REQUIRES_UPDATE = 'requires-update';

		public static function Configuration() {
			return self::$extension_configuration;
		}

		public static function enable($handle) {
			$extension = self::load($handle);
			$status = self::status($handle);

			$node = end(self::$extension_configuration->xpath("//extension[@handle='{$handle}'][1]"));

			if ($status == self::STATUS_NOT_INSTALLED) {
				if (is_callable([$extension, 'install'])) {
					$extension->install();
				}

				// Create the XML configuration object
				if (empty($node)) {
					$node = self::$extension_configuration->addChild('extension');
					$node->addAttribute('handle', $handle);
					$node->addAttribute('version', $extension->about()->version);
				}
			}

			else if ($status == self::STATUS_REQUIRES_UPDATE) {
				if (is_callable([$extension, 'update'])) {
					$extension->update($this->extension_configuration->xpath((string)"//extension[@handle='{$handle}']/@version"));
				}

				$node['version'] = $extension->about()->version;
			}

			if (is_callable([$extension, 'enable'])) {
				$extension->enable();
			}

			$node['status'] = self::STATUS_ENABLED;

			self::rebuildConfiguration();
		}

		public static function disable($handle) {
			$extension = self::load($handle);
			$node = end(self::$extension_configuration->xpath("//extension[@handle='{$handle}'][1]"));

			if (is_callable([$extension, 'disable'])) {
				$extension->disable();
			}

			$node['status'] = self::STATUS_DISABLED;

			self::rebuildConfiguration();
		}

		public static function uninstall($handle) {
			$extension = self::load($handle);
			$node = end(self::$extension_configuration->xpath("//extension[@handle='{$handle}'][1]"));

			if (is_callable([$extension, 'uninstall'])) {
				$extension->uninstall();
			}

			$node['status'] = self::STATUS_NOT_INSTALLED;

			self::rebuildConfiguration();
		}

		public static function findSubscribed($delegate, $page) {
			// Prepare the xpath
			$xpath = sprintf(
				"delegates/item[@delegate='%s'][@page='*' or %s]",
				$delegate,
				implode(' or ', array_map(function($value) { return "@page='{$value}'"; }, (array)$page))
			);

			$nodes = self::$extension_configuration->xpath("//extension[@status='enabled'][{$xpath}]");

			return $nodes;
		}

		public static function delegateSubscriptionCount($delegate, $page) {
			$nodes = self::findSubscribed($delegate, $page);

			return count($nodes);
		}

		public static function notify($delegate, $page, $context = []) {
			$nodes = self::findSubscribed($delegate, $page);
			$count = 0;

			if (empty($nodes) === false) {
				Profiler::begin('Executing delegate %delegate');
				Profiler::store('delegate', $delegate, 'system/delegate action/executed');

				// Prepare the xpath
				$xpath = sprintf(
					"delegates/item[@delegate='%s'][@page='*' or %s]",
					$delegate,
					implode(' or ', array_map(function($value) { return "@page='{$value}'"; }, (array)$page))
				);

				foreach ($nodes as $e) {
					$extension = self::load((string)$e->attributes()->handle);
					$delegates = $e->xpath($xpath);

					foreach ($delegates as $d) {
						$count++;

						if (is_callable([$extension, (string)$d->attributes()->callback])) {
							Profiler::begin('Executing delegate in %extension');
							Profiler::store('extension', $extension->handle, 'system/extension');
							Profiler::store('class', get_class($extension), 'system/class');
							Profiler::store('method', (string)$d->attributes()->callback, 'system/method');
							Profiler::store('location', $extension->file, 'system/resource');

							$extension->{(string)$d->attributes()->callback}($context);

							Profiler::end();
						}
					}
				}

				Profiler::end();
			}

			return $count;
		}

		public static function init($config = null) {
			if (isset(self::$extension_configuration) && isset($config) === false) {
				return null;
			}

			self::$extensions = [];

			// Load the configuration file:
			if (isset($config) === false) {
				$config = MANIFEST . '/extensions.xml';
			}

			if (file_exists($config) === false) {
				self::$extension_configuration = new SimpleXMLElement('<extensions></extensions>');
			}

			else {
				$previous = libxml_use_internal_errors(true);
				self::$extension_configuration = simplexml_load_file($config);
				libxml_use_internal_errors($previous);

				if ((self::$extension_configuration instanceof SimpleXMLElement) === false) {
					throw new ExtensionException('Failed to load Extension configuration file ' . $config);
				}
			}
		}

		public static function getPathFromClass($class) {
			return (
				isset(self::$extensions_class_to_path[$class])
					? self::$extensions_class_to_path[$class]
					: null
			);
		}

		public static function getHandleFromPath($pathname) {
			return str_replace(EXTENSIONS . '/', null, $pathname);
		}

		public static function saveConfiguration($pathname = null) {
			if (is_null($pathname)) {
				$pathname = MANIFEST . '/extensions.xml';
			}

			// Import the SimpleXMLElement object into a DOMDocument object. This ensures formatting is preserved
			$doc = dom_import_simplexml(self::$extension_configuration);
			$doc->ownerDocument->preserveWhiteSpace = false;
			$doc->ownerDocument->formatOutput = true;

			General::writeFile($pathname, $doc->ownerDocument->saveXML(), Symphony::Configuration()->core()->symphony->{'file-write-mode'});
		}

		public static function rebuildConfiguration($config_pathname = null) {
			$doc = new DOMDocument('1.0', 'utf-8');
			$doc->formatOutput = true;
			$doc->appendChild($doc->createElement('extensions'));
			$root = $doc->documentElement;

			foreach (new ExtensionIterator() as $extension) {
				$element = $doc->createElement('extension');
				$element->setAttribute('handle', $extension->handle);

				$node = end(self::$extension_configuration->xpath("//extension[@handle='{$extension->handle}'][1]"));

				if (!empty($node)) {
					$element->setAttribute('version', $node->attributes()->version);
					$element->setAttribute('status', $node->attributes()->status);
				}

				else {
					$element->setAttribute('version', $extension->about()->version);
					$element->setAttribute('status', self::status($extension->handle));
				}

				$root->appendChild($element);

				if (method_exists($extension, 'getSubscribedDelegates')) {
					$delegates = $doc->createElement('delegates');

					foreach ((array)$extension->getSubscribedDelegates() as $delegate) {
						$item = $doc->createElement('item');
						$item->setAttribute('page', $delegate['page']);
						$item->setAttribute('delegate', $delegate['delegate']);
						$item->setAttribute('callback', $delegate['callback']);
						$delegates->appendChild($item);
					}

					$element->appendChild($delegates);
				}
			}

		 	self::$extension_configuration = simplexml_import_dom($doc);
			self::saveConfiguration($config_pathname);
		}

		public static function load($handle) {
			$extensions = new ExtensionIterator();

			if (isset($extensions[$handle]) === false) {
				throw new ExtensionException('No extension found for ' . $handle);
			}

			return $extensions[$handle];
		}

		public static function status($handle) {
			$extensions = new ExtensionIterator();
			$status = self::STATUS_NOT_INSTALLED;

			if (isset($extensions[$handle])) {
				$extension = $extensions[$handle];
				$node = end(self::$extension_configuration->xpath("//extension[@handle='{$handle}'][1]"));

				if (empty($node) === false) {
					if (
						$node->attributes()->status == self::STATUS_ENABLED
						&& $node->attributes()->version != $extension->about()->version
					) {
						$node['status'] = self::STATUS_REQUIRES_UPDATE;
					}

					$status = (string)$node->attributes()->status;
				}
			}

			return $status;
		}
	}

	class ExtensionIterator extends ArrayIterator {
		protected static $cache;
		protected static $data;

		public static function buildCache() {
			$cache = self::$cache = new Cache(Cache::SOURCE_CORE, 'extensions');
			$directories = $cache->{'directories'};
			$last = $extensions = [];

			if (is_array($directories) === false) {
				Profiler::begin('Discovering extensions');

				foreach (new DirectoryIterator(EXTENSIONS) as $dir) {
					if (is_file($dir->getPathName() . '/extension.driver.php') === false) continue;

					Profiler::begin('Discovered extension %extension');

					$directories[] = (object)[
						'path' =>	$dir->getPathName(),
						'handle' =>	$dir->getFileName()
					];

					Profiler::store('extension', $dir->getFileName(), 'system/extension');
					Profiler::store('location', $dir->getPathName() . '/extension.driver.php', 'system/resource action/discovered');
					Profiler::end();
				}

				$cache->{'directories'} = $directories;

				Profiler::end();
			}

			Profiler::begin('Loading extensions');

			foreach ($directories as $dir) {
				Profiler::begin('Loaded extension %extension');

				try {
					$class = include_once $dir->path . '/extension.driver.php';
				}

				catch (Exception $error) {
					Symphony::Log()->pushExceptionToLog($error);

					Profiler::store('exception', $error->getMessage(), 'system/exeption');
					Profiler::end();

					continue;
				}

				if (Extension::Configuration()->xpath("//extension[@handle = '{$dir->handle}'][1]/@status")) {
					$status = current(Extension::Configuration()->xpath("//extension[@handle = '{$dir->handle}'][1]/@status"));
					$last = $status;
				}

				else {
					$status = $last;
				}

				$extensions[$dir->handle] = $extension = new $class();
				$extension->file = $dir->path . '/extension.driver.php';
				$extension->path = $dir->path;
				$extension->handle = $dir->handle;

				// The extension is enabled, does it have files to include?
				if (
					$status == Extension::STATUS_ENABLED
					&& (
						$extension instanceof ExtensionWithIncludesInterface
						|| $extension instanceof iExtensionIncludes
					)
				) {
					$extension->includeFiles();
				}

				Profiler::store('extension', $extension->handle, 'system/extension');
				Profiler::store('class', $class, 'system/class');
				Profiler::store('location', $extension->file, 'system/extension action/loaded');
				Profiler::store('enabled', $status == Extension::STATUS_ENABLED);
				Profiler::end();
			}

			Profiler::end();

			self::$data = $extensions;
		}

		public static function clearCachedFiles() {
			$cache = new Cache(Cache::SOURCE_CORE, 'extensions');
			$cache->purge();
		}

		public function __construct() {
			if (isset(self::$data) === false) {
				self::buildCache();
			}

			parent::__construct(self::$data);
		}
	}

	class ExtensionQuery extends FilterIterator {
		const STATUS = 'status';
		const TYPE = 'type';

		protected $filters;

		public function __construct() {
			$this->filters = [];

			parent::__construct(new ExtensionIterator());
		}

		public function setFilters(array $filters) {
			$this->filters = $filters;
		}

		public function accept() {
			$extension = $this->getInnerIterator()->current();

			foreach ($this->filters as $name => $value) {
				switch ($name) {
					case self::STATUS:
						$status = Extension::status($extension->handle);

						// Filter failed:
						if ($status !== $value) {
							return false;
						}

						break;

					case self::TYPE:
						$types = $extension->about()->type;

						// Must be an array:
						if (is_array($types) === false) {
							$types = [$types];
						}

						// Filter failed:
						if (in_array($value, $types) === false) {
							return false;
						}

						break;
				}
			}

			return true;
		}
	}