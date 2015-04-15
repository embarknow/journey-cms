<?php

require_once LIB . '/class.mutex.php';

class Cache
{
    /**
     * The directory where cache files.
     */
    protected static $directory = CACHE;

    /**
     * The unique ID currently loaded.
     */
    protected static $unique;

    /**
     * The cache belongs to the Symphony core.
     */
    const SOURCE_CORE = 'symphony';

    /**
     * The cache belongs to an extension.
     */
    const SOURCE_EXTENSION = 'extensions';

    /**
     * Cache storage key.
     */
    protected $key;

    /**
     * Cache time to live.
     */
    protected $ttl;

    /**
     * Set the current working cache directory.
     *
     * @param    string    $directory
     *
     * @throws    InvalidArgumentException
     */
    public static function setDirectory($directory = CACHE)
    {
        $found = realpath($directory);

        if ($found === false) {
            throw new InvalidArgumentException(sprintf(
                'Directory not found "%s".',
                $directory
            ));
        }

        self::$directory = $found;
    }

    /**
     * Create a new cache object.
     *
     * @param    string    $source
     *    One of `SOURCE_CORE` or `SOURCE_EXTENSION`.
     * @param    string    $role
     *    Handlised description of the cache.
     * @param    integer    $ttl
     *    Number of seconds to cache for.
     * @param    string    $unique
     *    A unique identifier used to namespace caches between sites.
     */
    public function __construct($source, $role, $ttl = 3600, $unique = null)
    {
        if ($unique === null) {
            if (isset(self::$unique)) {
                $unique = self::$unique;
            }

            if (is_file(self::$directory . '/id.cache')) {
                self::$unique = $unique = file_get_contents(self::$directory . '/id.cache');
            } else {
                self::$unique = $unique = uniqid();

                file_put_contents(self::$directory . '/id.cache', uniqid());
            }
        }

        $this->key = sprintf('%s,%s,%s', $source, $role, $unique);
        $this->ttl = $ttl;
    }

    public function __get($key)
    {
        if (isset($this->{$key}) === false) {
            return null;
        }

        return apc_fetch($this->key . ',' . $key);
    }

    public function __isset($key)
    {
        return apc_fetch($this->key . ',' . $key) !== false;
    }

    public function __set($key, $value)
    {
        return apc_store($this->key . ',' . $key, $value, $this->ttl);
    }

    public function __unset($key)
    {
        apc_delete($this->key . ',' . $key);
    }

    /**
     * Purge all caches belonging to this object.
     */
    public function purge()
    {
        apc_cache_search(
            sprintf('/^%s/', preg_quote($this->key)),
            function ($item) {
                apc_delete($item['info']);
            }
        );

        return true;
    }
}
