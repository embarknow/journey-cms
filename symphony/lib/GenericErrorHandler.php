<?php

use Log;
use ErrorException;

class GenericErrorHandler
{
    public static $enabled;
    protected static $log;

    public static $errorTypeStrings = array(
        E_NOTICE =>                 'Notice',
        E_WARNING =>                'Warning',
        E_ERROR =>                  'Error',
        E_PARSE =>                  'Parsing Error',

        E_CORE_ERROR =>             'Core Error',
        E_CORE_WARNING =>           'Core Warning',
        E_COMPILE_ERROR =>          'Compile Error',
        E_COMPILE_WARNING =>         'Compile Warning',

        E_USER_NOTICE =>            'User Notice',
        E_USER_WARNING =>           'User Warning',
        E_USER_ERROR =>             'User Error',

        E_STRICT =>                 'Strict Notice',
        E_RECOVERABLE_ERROR =>      'Recoverable Error'
    );

    public static function initialise(Log $log = null)
    {
        self::$enabled = true;

        if ($log instanceof Log) {
            self::$log = $log;
        }

        set_error_handler(array(__CLASS__, 'handler'), error_reporting());
    }

    public static function isEnabled()
    {
        return (bool)error_reporting() and self::$enabled;
    }

    public static function handler($code, $message, $file = null, $line = null)
    {
        throw new ErrorException($message, 0, $code, $file, $line);
    }
}
