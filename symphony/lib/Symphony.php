<?php

use Singleton;
use Exception;
use Log;
use GenericExceptionHandler;
use GenericErrorHandler;
use User;
use Cookie;
use Extension;
use Lang;
use Administration;
use Frontend;

use Embark\CMS\Database\Connection;
use Embark\CMS\Configuration\Controller as Configuration;
use Embark\CMS\SystemDateTime;

abstract class Symphony implements Singleton
{
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

    public function lang()
    {
        return self::$_lang;
    }

    public function initialiseConfiguration()
    {
        self::$Configuration = $config = Configuration::read('main');

        date_default_timezone_set($config['region']['timezone']);

        self::$_lang = ($config['lang'] ? $config['lang'] : 'en');

        define_safe('__SYM_DATE_FORMAT__', $config['region']['date-format']);
        define_safe('__SYM_TIME_FORMAT__', $config['region']['time-format']);
        define_safe('__SYM_DATETIME_FORMAT__', sprintf('%s %s', __SYM_DATE_FORMAT__, __SYM_TIME_FORMAT__));
        define_safe('ADMIN_URL', sprintf('%s/%s', URL, trim($config['admin']['path'], '/')));
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
        } catch (Exception $e) {
            $cookie_path = '/';
        }

        define_safe('__SYM_COOKIE_PATH__', $cookie_path);
        define_safe('__SYM_COOKIE_PREFIX__', self::Configuration()['session']['cookie-prefix']);

        self::$Cookie = new Cookie(__SYM_COOKIE_PREFIX__, TWO_WEEKS, __SYM_COOKIE_PATH__, null, true);
    }

    public static function Cookie()
    {
        return self::$Cookie;
    }

    public function initialiseDatabase()
    {
        $conf = Configuration::read('database');
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
        Symphony::Log()->setArchive(self::Configuration()['logging']['archive']);
        Symphony::Log()->setMaxSize(intval(self::Configuration()['logging']['maxsize']));

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

    public function isLoggedIn()
    {
        return Symphony::User() instanceof User;
    }

    public function logout()
    {
        Symphony::Cookie()->expire();
    }

    public function login($username, $password, $isHash = false)
    {
        $user = User::loadFromCredentials($username, $password, $isHash);

        if ($user instanceof User) {
            if ($user->login()) {
                self::$User = $user;

                return true;
            }
        }

        return false;
    }

    public function loginFromToken($token)
    {
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
        } else {
            return Frontend::instance();
        }
    }
}

return 'Symphony';
