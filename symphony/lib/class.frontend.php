<?php

use Embark\CMS\UserDateTime;
use Embark\CMS\Structures\ParameterPool as Context;

	require_once(LIB . '/class.symphony.php');
	require_once(LIB . '/class.xmldocument.php');
	require_once(LIB . '/class.lang.php');
	require_once(LIB . '/class.context.php');

	Class FrontendPageNotFoundException extends SymphonyErrorPage{
		public function __construct(View $page=NULL){

			if(is_null($page)){
				$views = View::findFromType('404');
				$page = array_shift($views);
			}

			parent::__construct(
				__('The page you requested does not exist.'),
				__('Page Not Found'),
				$page,
				array('header' => 'HTTP/1.0 404 Not Found')
			);
		}
	}

	Class FrontendPageNotFoundExceptionHandler extends SymphonyErrorPageHandler{
		public static function render($e){
			parent::render($e);
		}
	}

	Class Frontend extends Symphony {
		protected static $view;
		protected static $Document;
		protected static $Parameters;
		protected static $Headers;

		public static function instance() {
			if (!(self::$_instance instanceof Frontend)) {
				self::$_instance = new self;
			}

			return self::$_instance;
		}

		public static function loadedView(){
			return self::$view;
		}

		public static function Headers() {
			return self::$Headers;
		}

		public static function Document() {
			return self::$Document;
		}

		public static function Parameters() {
			return self::$Parameters;
		}

		public function __construct() {
			parent::__construct();

			self::$Headers = new DocumentHeaders;

			self::$Document = new XMLDocument;
			self::$Document->appendChild(
				self::$Document->createElement('data')
			);

			Widget::init(self::$Document);
		}

		public function resolve($url=NULL){
			try {
				if (is_null($url)) {
					$views = View::findFromType('index');
					self::$view = array_shift($views);
				}

				else {
					self::$view = View::loadFromURL($url);
				}

				if (!(self::$view instanceof View)) {
					throw new Exception('Page not found');
				}

				if (!Frontend::instance()->isLoggedIn() && in_array('admin', self::$view->types)) {
					$views = View::findFromType('403');
					self::$view = array_shift($views);

					if (!(self::$view instanceof View)) {
						throw new SymphonyErrorPage(
							__('Please <a href="%s">login</a> to view this page.', array(ADMIN_URL . '/login/')),
							__('Forbidden'), NULL,
							array('HTTP/1.0 403 Forbidden')
						);
					}
				}
			}

			catch (Exception $e) {
				$views = View::findFromType('404');
				self::$view = array_shift($views);

				if (!(self::$view instanceof View)) {
					throw new FrontendPageNotFoundException($url);
				}
			}
		}

		public function display($url=NULL){
			Profiler::begin('Render the current page');

			self::$Parameters = new Context();

			// Default headers. Can be overwritten later
			//self::$Headers->append('HTTP/1.0 200 OK');
			self::$Headers->append('Content-Type', 'text/html;charset=utf-8');
			self::$Headers->append('Expires', 'Mon, 12 Dec 1982 06:14:00 GMT');
			self::$Headers->append('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
			self::$Headers->append('Cache-Control', 'no-cache, must-revalidate, max-age=0');
			self::$Headers->append('Pragma', 'no-cache');


			// RESOLVING THE VIEW -----------------------------

			Profiler::begin('Resolving the view');

			####
			# Delegate: FrontendPreInitialise
			# Description: TODO
			# Global: Yes
			Extension::notify(
				'FrontendPreInitialise',
				'/frontend/',
				array(
					'view' => &self::$view,
					'url' => &$url
				)
			);

			if(!(self::$view instanceof View)){
				$this->resolve($url);
			}

			####
			# Delegate: FrontendPostInitialise
			Extension::notify(
				'FrontendPostInitialise',
				'/frontend/',
				array(
					'view' => &self::$view
				)
			);

			if (isset(self::$view->{'pathname'})) {
				Profiler::store('location', self::$view->{'pathname'}, 'system/resource action/loaded text/xml+xslt');
			}

			Profiler::store('url', $url);
			Profiler::end();


			// SETTING UP PARAMETERS --------------------------

			Profiler::begin('Setting up parameters');

			$clean = function($parameter) {
				return htmlspecialchars($parameter, ENT_QUOTES, 'UTF-8');
			};

			// Make sure all URL parameters defined by the view are set:
			if (isset(self::$view->{'url-parameters'}) && is_array(self::$view->{'url-parameters'})) {
				foreach (self::$view->{'url-parameters'} as $parameter) {
					self::$Parameters->{$parameter} = null;
				}

				foreach (self::$view->parameters() as $parameter => $value) {
					self::$Parameters->{$parameter} = htmlspecialchars(str_replace(' ', '+', $value), ENT_QUOTES, 'UTF-8');
				}
			}

			// Import actual GET parameters:
			if (is_array($_GET) && empty($_GET) === false) {
				$sanitize = function($value) {
					return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
				};

				foreach ($_GET as $parameter => $value) {
					if (in_array($parameter, array('symphony-page'))) continue;

					if (is_array($value)) {
						self::$Parameters->{$parameter} = array_map($sanitize, $value);
					}

					else {
						self::$Parameters->{$parameter} = $sanitize($value);
					}
				}
			}

			// Import cookie values as parameters:
			if (is_array($_COOKIE[__SYM_COOKIE_PREFIX__]) && !empty($_COOKIE[__SYM_COOKIE_PREFIX__])) {
				foreach ($_COOKIE[__SYM_COOKIE_PREFIX__] as $parameter => $value) {
					self::$Parameters->{"cookie-{$parameter}"} = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
				}
			}

			// Non-overridable parameters:
			$root_page = array_shift(explode('/', self::$view->parent()->path));
			$current_url = parse_url(URL);
			$current_url['path'] = $_SERVER['REQUEST_URI'];
			$current_url = unparse_url($current_url);
			$date = new UserDateTime();

			self::$Parameters->register(array(
				'today' =>					$date->format('Y-m-d'),
				'current-time' =>			$date->format('H:i'),
				'this-year' =>				$date->format('Y'),
				'this-month' =>				$date->format('m'),
				'this-day' =>				$date->format('d'),
				'timezone' =>				date_default_timezone_get(),
				'website-name' =>			Symphony::Configuration()->main()->name,
				'root' =>					URL,
				'relative-root' =>			ROOT_PATH,
				'workspace' =>				URL . '/workspace',
				'page-title' =>				self::$view->title,
				'root-page' =>				(
												$root_page !== null
													? $root_page
													: self::$view->handle
											),
				'current-page' =>			self::$view->handle,
				'current-path' =>			(
												CURRENT_PATH
													? CURRENT_PATH
													: '/'
											),
				'parent-path' =>			'/' . self::$view->path,
				'current-url' =>			$current_url
			));

			Profiler::end();


			// RENDER THE VIEW --------------------------

			// Can ask the view to operate on an existing
			// Document. Useful if we pass it around beyond
			// the scope of View::render()

			Profiler::begin('Render the view');

			####
			# Delegate: FrontendPreRender
			# Description: TODO
			# Global: Yes
			Extension::notify(
				'FrontendPreRender',
				'/frontend/',
				array(
					'view' =>		self::$view,
					'parameters' =>	self::$Parameters,
					'document' =>	self::$Document,
					'headers' =>	self::$Headers
				)
			);

			$output = self::$view->render(self::$Parameters, self::$Document, self::$Headers);

			####
			# Delegate: FrontendPostRender
			# Description: TODO
			# Global: Yes
			Extension::notify(
				'FrontendPostRender',
				'/frontend/',
				array(
					'output' =>		$output,
					'headers' =>	self::$Headers
				)
			);

			// Find the current content type:
			$headers = self::$Headers->headers();
			$content_type = isset($headers['content-type'])
				? $headers['content-type']
				: null;

			self::Headers()->render();

			// Send as HTML:
			if (preg_match('%\b(application|text)/html%', $content_type)) {
				$output = $output->saveHTML();
			}

			// Send as XML:
			else {
				$output = $output->saveXML();
			}

			Profiler::end();
			Profiler::end();

			return $output;
		}
	}

	return 'Frontend';
