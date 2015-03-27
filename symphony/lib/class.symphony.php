<?php

use Embark\CMS\Database\Connection;
use Embark\CMS\Configuration\Loader as Configuration;
use Embark\CMS\SystemDateTime;

	require_once 'class.errorhandler.php';

	// require_once 'class.dbc.php';
	// require_once 'class.configuration.php';
	// require_once 'class.datetimeobj.php';
	require_once 'class.log.php';
	require_once 'class.cookie.php';
	require_once 'interface.singleton.php';
	require_once 'class.cache.php';

	require_once 'class.section.php';
	require_once 'class.view.php';
	require_once 'class.widget.php';
	require_once 'class.general.php';
	require_once 'class.user.php';
	require_once 'class.xslproc.php';

	require_once 'class.extension.php';
	require_once 'class.cryptography.php';

	Class SymphonyErrorPageHandler extends GenericExceptionHandler{
		public static function render($e){

			if(is_null($e->getTemplatePath())){
				header('HTTP/1.0 500 Server Error');
				echo '<h1>Symphony Fatal Error</h1><p>'.$e->getMessage().'</p>';
				exit;
			}

			$xml = new DOMDocument('1.0', 'utf-8');
			$xml->formatOutput = true;

			$root = $xml->createElement('data');
			$xml->appendChild($root);

			$root->appendChild($xml->createElement('heading', General::sanitize($e->getHeading())));
			$root->appendChild($xml->createElement('message', General::sanitize(
				$e->getMessageObject() instanceof SymphonyDOMElement ? (string)$e->getMessageObject() : trim($e->getMessage())
			)));
			if(!is_null($e->getDescription())){
				$root->appendChild($xml->createElement('description', General::sanitize($e->getDescription())));
			}

			header('HTTP/1.0 500 Server Error');
			header('Content-Type: text/html; charset=UTF-8');
			header('Symphony-Error-Type: ' . $e->getErrorType());

			foreach($e->getHeaders() as $header){
				header($header);
			}

			$output = parent::__transform($xml, basename($e->getTemplatePath()));

			header(sprintf('Content-Length: %d', strlen($output)));
			echo $output;

			exit;
		}
	}

	Class SymphonyErrorPage extends Exception{

		private $_heading;
		private $_message;
		private $_type;
		private $_headers;
		private $_messageObject;
		private $_help_line;

		public function __construct($message, $heading='Fatal Error', $description=NULL, array $headers=array()){

			$this->_messageObject = NULL;
			if($message instanceof SymphonyDOMElement){
				$this->_messageObject = $message;
				$message = (string)$this->_messageObject;
			}

			parent::__construct($message);

			$this->_heading = $heading;
			$this->_headers = $headers;
			$this->_description = $description;
		}

		public function getMessageObject(){
			return $this->_messageObject;
		}

		public function getHeading(){
			return $this->_heading;
		}

		public function getErrorType(){
			return $this->_template;
		}

		public function getDescription(){
			return $this->_description;
		}

		public function getTemplatePath(){

			$template = NULL;

			if(file_exists(MANIFEST . '/templates/exception.symphony.xsl')){
				$template = MANIFEST . '/templates/exception.symphony.xsl';
			}

			elseif(file_exists(TEMPLATES . '/exception.symphony.xsl')){
				$template = TEMPLATES . '/exception.symphony.xsl';
			}

			return $template;
		}

		public function getHeaders(){
			return $this->_headers;
		}
	}

	Abstract Class Symphony implements Singleton{
		public static $Log;

		protected static $Configuration;
		protected static $Cookie;
		protected static $Database;
		protected static $User;

		protected static $_lang;
		protected static $_instance;

		protected function __construct()
		{
			$this->initialiseConfiguration();
			$this->initialiseLog();
			$this->initialiseDatabase();
			$this->initialiseCookie();
			$this->initialiseExtensions();
			$this->initialiseLanguage();
			$this->initialiseUser();
		}

		public function lang() {
			return self::$_lang;
		}

		public function initialiseConfiguration($path = CONF)
		{
			self::$Configuration = new Configuration($path);

			date_default_timezone_set(self::Configuration()->main()->region->timezone);

			self::$_lang = (self::Configuration()->main()->lang ? self::Configuration()->main()->lang : 'en');

			define_safe('__SYM_DATE_FORMAT__', self::Configuration()->main()->region->{'date-format'});
			define_safe('__SYM_TIME_FORMAT__', self::Configuration()->main()->region->{'time-format'});
			define_safe('__SYM_DATETIME_FORMAT__', sprintf('%s %s', __SYM_DATE_FORMAT__, __SYM_TIME_FORMAT__));
			define_safe('ADMIN_URL', sprintf('%s/%s', URL, trim(self::Configuration()->main()->admin->path, '/')));
		}

		public static function Configuration()
		{
			return self::$Configuration;
		}

		public function initialiseCookie()
		{
			try {
				$cookie_path = parse_url(URL, PHP_URL_PATH);
				$cookie_path = '/' . trim($cookie_path, '/');
			}

			catch (Exception $e) {
				$cookie_path = '/';
			}

			define_safe('__SYM_COOKIE_PATH__', $cookie_path);
			define_safe('__SYM_COOKIE_PREFIX__', self::Configuration()->main()->session->{'cookie-prefix'});

			self::$Cookie = new Cookie(__SYM_COOKIE_PREFIX__, TWO_WEEKS, __SYM_COOKIE_PATH__, null, true);
		}

		public static function Cookie()
		{
			return self::$Cookie;
		}

		public function initialiseDatabase()
		{
			$conf = (object)Symphony::Configuration()->database();
			$database = new Connection($conf);
			$database->connect();

			self::$Database = $database;

			return true;
		}

		public static function Database()
		{
			return self::$Database;
		}

		public function initialiseExtensions()
		{
			Extension::init();
		}

		public function initialiseLanguage()
		{
			Lang::loadAll(true);
		}

		public function initialiseLog()
		{
			self::$Log = new Log(ACTIVITY_LOG);
			Symphony::Log()->setArchive((self::Configuration()->main()->logging->archive == '1' ? true : false));
			Symphony::Log()->setMaxSize(intval(self::Configuration()->main()->logging->maxsize));

			if (Symphony::Log()->open() == 1) {
				Symphony::Log()->writeToLog('Symphony Log', true);
				Symphony::Log()->writeToLog('--------------------------------------------', true);
			}

			GenericExceptionHandler::initialise(Symphony::Log());
			GenericErrorHandler::initialise(Symphony::Log());
		}

		public static function Log()
		{
			return self::$Log;
		}

		public function initialiseUser()
		{
			// Use the login token:
			if (isset($_REQUEST['auth-token']) && $_REQUEST['auth-token'] && strlen($_REQUEST['auth-token']) == 8) {
				$user = User::loadFromAuthToken($_REQUEST['auth-token']);
			}

			// Try and use the cookie:
			if (!($user instanceof User)) {
				$username = Symphony::Cookie()->get('username');
				$password = Symphony::Cookie()->get('pass');
				$user = User::loadFromCredentials($username, $password, true);
			}

			if ($user instanceof User) {
				if ($user->login()) {
					self::$User = $user;
				}
			}

			// The credentials were invalid, remove them:
			else {
				Symphony::Cookie()->expire();
			}
		}

		public static function User()
		{
			return self::$User;
		}

		public function isLoggedIn() {
			return Symphony::User() instanceof User;
		}

		public function logout() {
			Symphony::Cookie()->expire();
		}

		public function login($username, $password, $isHash = false) {
			$user = User::loadFromCredentials($username, $password, $isHash);

			if ($user instanceof User) {
				if ($user->login()) {
					self::$User = $user;

					return true;
				}
			}

			return false;
		}

		public function loginFromToken($token) {
			$user = User::loadFromAuthToken($token);

			if ($user instanceof User) {
				if ($user->login()) {
					self::$User = $user;

					return true;
				}
			}

			return false;
		}

		public static function Parent()
		{
			if (class_exists('Administration')) {
				return Administration::instance();
			}

			else {
				return Frontend::instance();
			}
		}
	}

	return 'Symphony';