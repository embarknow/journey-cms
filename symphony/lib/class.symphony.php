<?php

use Embark\CMS\Database\Connection;
use Embark\CMS\Configuration\Loader as Configuration;

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
		protected static $Database;

		protected static $_lang;

		public $Cookie;
		public $User;

		protected static $_instance;

		protected function __construct(){
			self::$Configuration = new Configuration;

			date_default_timezone_set(self::Configuration()->main()->region->timezone);

			self::$_lang = (self::Configuration()->main()->lang ? self::Configuration()->main()->lang : 'en');

			define_safe('__SYM_DATE_FORMAT__', self::Configuration()->main()->region->{'date-format'});
			define_safe('__SYM_TIME_FORMAT__', self::Configuration()->main()->region->{'time-format'});
			define_safe('__SYM_DATETIME_FORMAT__', sprintf('%s %s', __SYM_DATE_FORMAT__, __SYM_TIME_FORMAT__));
			define_safe('ADMIN_URL', sprintf('%s/%s', URL, trim(self::Configuration()->main()->admin->path, '/')));

			$this->initialiseLog();

			GenericExceptionHandler::initialise(self::$Log);
			GenericErrorHandler::initialise(self::$Log);

			$this->initialiseDatabase();
			$this->initialiseCookie();

			Extension::init();

			Lang::loadAll(true);

			// HACK!
			$this->Cookie->get('blah');
		}

		public function lang() {
			return self::$_lang;
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

			$this->Cookie = new Cookie(__SYM_COOKIE_PREFIX__, TWO_WEEKS, __SYM_COOKIE_PATH__, null, true);
		}

		public static function Configuration()
		{
			return self::$Configuration;
		}

		public static function Database()
		{
			return self::$Database;
		}

		public static function Log()
		{
			return self::$Log;
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

		public function initialiseDatabase()
		{
			$conf = (object)Symphony::Configuration()->database();
			$database = new Connection($conf);
			$database->connect();

			self::$Database = $database;

			return true;
		}

		public function initialiseLog(){

			self::$Log = new Log(ACTIVITY_LOG);
			self::$Log->setArchive((self::Configuration()->main()->logging->archive == '1' ? true : false));
			self::$Log->setMaxSize(intval(self::Configuration()->main()->logging->maxsize));

			if(self::$Log->open() == 1){
				self::$Log->writeToLog('Symphony Log', true);
				self::$Log->writeToLog('--------------------------------------------', true);
			}

		}

		public function isLoggedIn() {
			if ($this->User) return true;

			if (isset($_REQUEST['auth-token']) && $_REQUEST['auth-token'] && strlen($_REQUEST['auth-token']) == 8) {
				return $this->loginFromToken($_REQUEST['auth-token']);
			}

			$username = $this->Cookie->get('username');
			$password = $this->Cookie->get('pass');

			if (strlen(trim($username)) > 0 && strlen(trim($password)) > 0) {
				$result = Symphony::Database()->query(
					"
						SELECT
							u.id
						FROM
							tbl_users AS u
						WHERE
							u.username = '%s'
							AND u.password = '%s'
						LIMIT 1
					",
					array($username, $password)
				);

				if ($result->valid()) {
					$this->_user_id = $result->current()->id;

					Symphony::Database()->update(
						'tbl_users',
						array('last_seen' => (new DateTime)->format('Y-m-d H:i:s')),
						array($this->_user_id),
						"`id` = '%s'"
					);

					$this->User = User::load($this->_user_id);
					$this->reloadLangFromUserPreference();

					return true;
				}
			}

			$this->Cookie->expire();

			return false;
		}

		public function logout() {
			$this->Cookie->expire();
		}

		// TODO: Most of this logic is duplicated with the isLoggedIn function.
		public function login($username, $password, $isHash = false) {
			if (strlen(trim($username)) <= 0 || strlen(trim($password)) <= 0) return false;

			$user = User::loadUserFromUsername($username);

			if ($user instanceof User && Cryptography::compare($password, $user->password, $isHash)) {
				if (Cryptography::requiresMigration($user->password)) {
					$user->password = Cryptography::hash($password);
				}

				$this->Cookie->set('username', $username);
				$this->Cookie->set('pass', $user->password);

				$this->User = $user;
				$user->last_seen = (new DateTime)->format('Y-m-d H:i:s');
				$this->reloadLangFromUserPreference();

				User::save($user);
				return true;
			}

			return false;
		}

		public function loginFromToken($token) {
			$token = Symphony::Database()->escape($token);

			if (strlen(trim($token)) == 0) return false;

			if (strlen($token) == 6) {
				$date = new DateTime();
				$date->setTimeZone(new DateTimeZone('UTC'));

				$result = Symphony::Database()->query("
						SELECT
							`u`.id, `u`.username, `u`.password
						FROM
							`tbl_users` AS u, `tbl_forgotpass` AS f
						WHERE
							`u`.id = `f`.user_id
						AND
							`f`.expiry > '%s'
						AND
							`f`.token = '%s'
						LIMIT 1
					",
					array(
						$date->format(DateTime::W3C),
						$token
					)
				);

				Symphony::Database()->delete('tbl_forgotpass', array($token), "`token` = '%s'");
			}

			else {
				$result = Symphony::Database()->query("
						SELECT
							id, username, password
						FROM
							`tbl_users`
						WHERE
							SUBSTR(MD5(CONCAT(`username`, `password`)), 1, 8) = '%s'
						AND
							auth_token_active = 'yes'
						LIMIT 1
					",
					array($token)
				);
			}

			if ($result->valid()) {
				$row = $result->current();
				$this->_user_id = $row->id;

				$this->User = User::load($this->_user_id);
				$this->Cookie->set('username', $row->username);
				$this->Cookie->set('pass', $row->password);

				$date = new DateTime();
				$date->setTimeZone(new DateTimeZone('UTC'));

				Symphony::Database()->update(
					'tbl_users',
					array('last_seen' => $date->format('Y-m-d H:i:s')),
					array($this->_user_id),
					"`id` = '%d'"
				);

				$this->reloadLangFromUserPreference();

				return true;
			}

			return false;

		}

		public function reloadLangFromUserPreference() {
			$lang = $this->User->language;

			if($lang && $lang != self::lang()){
				self::$_lang = $lang;
				if($lang != 'en') {
					Lang::loadAll();
				}
				else {
					// As there is no English dictionary the default dictionary needs to be cleared
					Lang::clear();
				}
			}
		}
	}

	return 'Symphony';