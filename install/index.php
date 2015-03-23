<?php
	if(isset($_GET['info'])) {
		phpinfo();
		die();
	}

	define('DOCROOT', rtrim(realpath('..'), '\\/'));
	define('DOMAIN', rtrim(rtrim($_SERVER['HTTP_HOST'], '\\/') . dirname($_SERVER['REQUEST_URI']), '\\/'));
	define('VERSION', '3.0.0beta');

	set_include_path(get_include_path() . PATH_SEPARATOR . realpath('../symphony/lib/'));

	require DOCROOT . '/symphony/bundle.php';
	require DOCROOT . '/symphony/lib/class.frontend.php';
	require DOCROOT . '/symphony/lib/class.htmldocument.php';

	class Installer extends Symphony {
		public static function instance() {
			if (!(self::$_instance instanceof Installer)) {
				self::$_instance = new self;
			}

			return self::$_instance;
		}

		protected function __construct(){
			self::$Configuration = new Configuration(__DIR__ . '/conf');
			$settings = self::Configuration()->main();

			DateTimeObj::setDefaultTimezone($settings->region->timezone);

			self::$_lang = (
				$settings->lang
					? $settings->lang
					: 'en'
			);

			define_safe('__SYM_DATE_FORMAT__', $settings->region->{'date-format'});
			define_safe('__SYM_TIME_FORMAT__', $settings->region->{'time-format'});
			define_safe('__SYM_DATETIME_FORMAT__', sprintf('%s %s', __SYM_DATE_FORMAT__, __SYM_TIME_FORMAT__));
			define_safe('ADMIN_URL', sprintf('%s/%s', URL, trim($settings->admin->path, '/')));

			$this->initialiseLog();

			GenericExceptionHandler::initialise(self::$Log);
			GenericErrorHandler::initialise(self::$Log);

			// $this->initialiseDatabase();
			$this->initialiseCookie();

			Extension::init();

			Lang::loadAll(true);

			// HACK!
			$this->Cookie->get('blah');
		}

		public static function setDatabase($db)
		{
			self::$Database = $db;
		}
	}

	Installer::instance();

	//$clean_path = rtrim($_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']), '/\\');
	//$clean_path = preg_replace(array('/\/{2,}/i', '/\/install$/i'), array('/', NULL), $clean_path);

	//define('DOMAIN', $clean_path);
	//define('URL', 'http://' . $clean_path);

	Lang::load(realpath('../symphony/lang') . '/lang.en.php', 'en', true);

	function createPanel(DOMElement &$wrapper, $heading, $tooltip, $error_message=NULL){
		$div = $wrapper->ownerDocument->createElement('div');

		$help = $wrapper->ownerDocument->createElement('div');
		$help->setAttribute('class', 'help');
		$help->appendChild($wrapper->ownerDocument->createElement('h2', $heading));
		$help->appendChild($wrapper->ownerDocument->createElement('p', $tooltip));
		$div->appendChild($help);

		$panel = $wrapper->ownerDocument->createElement('div');
		$panel->setAttribute('class', 'panel');
		$div->appendChild($panel);

		$wrapper->appendChild($div);

		if(!is_null($error_message)){

			$extended = $wrapper->ownerDocument->createElement('div');
			$extended->setAttribute('class', 'extended error');
			$panel->appendChild($extended);
			$div = $wrapper->ownerDocument->createElement('div');
			$extended->appendChild($div);

			$div->appendChild($wrapper->ownerDocument->createElement('p', $error_message));

			$panel->appendChild($extended);
		}

		return $panel;
	}

	function missing($value){
		if(!is_array($value)) $value = (array)$value;

		foreach($value as $v){
			if(strlen(trim($v)) == 0) return true;
		}

		return false;
	}

	$errors = new MessageStack();
	$mainConf = Installer::Configuration()->main();
	$databaseConf = Installer::Configuration()->database();

	if (isset($_POST['action']['install'])) {
		$data = $_POST;

	// Database --------------------------------------------------------------------------------------------------

		$data['database'] = array_map('trim', $data['database']);

		foreach ($data['database'] as $key => $value) {
			$databaseConf[$key] = $value;
		}

		if (missing(array(
			$data['database']['host'],
			$data['database']['port'],
			$data['database']['user'],
			$data['database']['password'],
			$data['database']['database'],
			$data['database']['table-prefix']
		))) {
			$errors->append('database', 'Please fill in all of the fields.');
		}

		// Test the database connection:
		if ($errors->length() == 0) {
			$db = new Connection($databaseConf);

			try {
				$db->connect();
			}

			catch (PDOException $e) {
				$errors->append('database', 'Could not establish database connection. The following error was returned: ' . $e->getMessage());
			}

			if ($errors->length() == 0) {
			/// Create the .htaccess ------------------------------------------------------------------

				$rewrite_base = preg_replace('/\/install$/i', NULL, dirname($_SERVER['PHP_SELF']));

				$htaccess = sprintf(
					file_get_contents('assets/template.htaccess.txt'),
					empty($rewrite_base) ? '/' : $rewrite_base
				);

				// Cannot write .htaccess:
				if (false === General::writeFile(
					realpath('..') . '/.htaccess',
					$htaccess,
					0775
				)) {
					throw new Exception('Could not write .htaccess file. TODO: Handle this by recording to the log and showing nicer error page.');
				}

			/// Create Folder Structures ---------------------------------------------------------------

				// These folders are necessary, and can be created if missing
				$folders = [
					'workspace',
					'workspace/views',
					'workspace/utilities',
					'workspace/sections',
					'workspace/data-sources',
					'workspace/events',
					'manifest',
					'manifest/conf',
					'manifest/logs',
					'manifest/templates',
					'manifest/tmp',
					'manifest/cache'
				];

				foreach ($folders as $folder) {
					$path = realpath('..') . "/{$folder}";

					if (false === is_dir($path) && false === mkdir($path, 0775)) {
						throw new Exception('Could not create directory ' . $path . '. TODO: Handle this by recording to the log and showing nicer error page.');
					}
				}

			/// Save the config ------------------------------------------------------------------------

				$mainConf->save(realpath('..') . '/manifest/conf/main.xml');
				$databaseConf->save(realpath('..') . '/manifest/conf/database.xml');
			}

			// Import the database:
			if ($errors->length() == 0) {
				try {
					$queries = require __DIR__ . '/assets/queries.php';

					foreach ($queries as $query) {
						$db->execute($query);
					}

					// Create the default user
					$db->insert('tbl_users', [
						'username' =>			'admin',
						'password' =>			uniqid(true),
						'first_name' =>			'Site',
						'last_name' =>			'Admin',
						'email' =>				'admin@localhost',
						'default_section' =>	'articles',
						'auth_token_active' =>	'yes',
						'language' =>			'en'
					]);

					Installer::setDatabase($db);
				}

				catch (Exception $e) {
					$errors->append('database', $e->getMessage());
				}
			}

			// Log the user in:
			if ($errors->length() == 0) {
				$user = User::load(1);
				$token = $user->createAuthToken();

				redirect(URL . '/symphony/system/settings/?auth-token=' . $token);
			}
		}
	}





	$Document = new HTMLDocument('1.0', 'utf-8', 'html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"');

	$Document->Headers->append('Expires', 'Mon, 12 Dec 1982 06:14:00 GMT');
	$Document->Headers->append('Last-Modified', gmdate('r'));
	$Document->Headers->append('Cache-Control', 'no-cache, must-revalidate, max-age=0');
	$Document->Headers->append('Pragma', 'no-cache');

	Widget::init($Document);

	$Document->insertNodeIntoHead($Document->createElement('title', 'Symphony Installation'));

	$meta = $Document->createElement('meta');
	$meta->setAttribute('http-equiv', 'Content-Type');
	$meta->setAttribute('content', 'text/html; charset=UTF-8');
	$Document->insertNodeIntoHead($meta);

	$Document->insertNodeIntoHead($Document->createStylesheetElement('assets/styles.css'));

	$form = $Document->createElement('form');
	$form->setAttribute('method', 'POST');
	$form->setAttribute('action', '');
	$Document->appendChild($form);

	$layout = $Document->createElement('div');
	$layout->setAttribute('id', 'layout');
	$form->appendChild($layout);


	// About panel ---------------------------------------------------------------------------------------------------

	$div = $Document->createElement('div');
	$layout->appendChild($div);

	$about = $Document->createElement('div');
	$about->setAttribute('class', 'about');
	$about->appendChild($Document->createElement('h1', 'You are Installing Symphony'));
	$about->appendChild($Document->createElement('p', 'Version 3.0.0 alpha'));
	$div->appendChild($about);


	// Database Connection -------------------------------------------------------------------------------------------

	$panel = createPanel($layout, 'Database', 'Access details and database preferences', $errors->{'database'});

	$label = Widget::Label('Database', NULL, array('class' => 'input'));
	$input = Widget::Input('database[database]', $databaseConf['database']);
	$label->appendChild($input);
	$panel->appendChild($label);

	$group = $Document->createElement('div');
	$group->setAttribute('class', 'group');

	$label = Widget::Label('Username', NULL, array('class' => 'input'));
	$input = Widget::Input('database[user]', $databaseConf['user']);
	$label->appendChild($input);
	$group->appendChild($label);

	$label = Widget::Label('Password', NULL, array('class' => 'input'));
	$input = Widget::Input('database[password]', $databaseConf['password'], 'password');
	$label->appendChild($input);
	$group->appendChild($label);

	$panel->appendChild($group);

	$extended = $Document->createElement('div');
	$extended->setAttribute('class', 'extended');
	$panel->appendChild($extended);
	$div = $Document->createElement('div');
	$extended->appendChild($div);
	$group = $Document->createElement('div');
	$group->setAttribute('class', 'group');
	$div->appendChild($group);

	$label = Widget::Label('Host', NULL, array('class' => 'input'));
	$input = Widget::Input('database[host]', $databaseConf['host']);
	$label->appendChild($input);
	$group->appendChild($label);

	$label = Widget::Label('Port', NULL, array('class' => 'input'));
	$input = Widget::Input('database[port]', $databaseConf['port']);
	$label->appendChild($input);
	$group->appendChild($label);

	$label = Widget::Label('Table Prefix', NULL, array('class' => 'input'));
	$input = Widget::Input('database[table-prefix]', $databaseConf['table-prefix']);
	$label->appendChild($input);
	$group->appendChild($label);


	// Submit --------------------------------------------------------------------------------------------------------

	$div = $Document->createElement('div');
	$layout->appendChild($div);

	$submit = $Document->createElement('div');
	$submit->setAttribute('class', 'content submission');
	$p = $Document->createElement('p');
	$button = $Document->createElement('button', 'Install Symphony');
	$button->setAttribute('name', 'action[install]');
	$p->appendChild($button);
	$submit->appendChild($p);
	$div->appendChild($submit);

	$output = (string)$Document;

	$Document->Headers->append('Content-Length', strlen($output));

	$Document->Headers->render();
	echo $output;
	die();
