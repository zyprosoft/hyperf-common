<?php


namespace ZYProSoft\Facade;

use Hyperf\Di\Exception\Exception;
use Hyperf\Utils\ApplicationContext;
use Psr\SimpleCache\CacheInterface;

/**
 * Class Cache
 * @package ZYProSoft\Facade
 * @method static mixed get($key, $default = null)
 * @method static bool set($key, $value, $ttl = null)
 * @method static bool delete($key)
 * @method static bool clear()
 * @method static iterable getMultiple($keys, $default = null)
 * @method static bool setMultiple($values, $ttl = null)
 * @method static bool deleteMultiple($keys)
 * @method static bool has($key)
 */
class Cache
{
    public static function cache()
    {
        return ApplicationContext::getContainer()->get(CacheInterface::class);
    }

    public static function __callStatic($name, $arguments)
    {
        if (method_exists(static::cache(), $name)) {
            call([static::cache(), $name], $arguments);
        }else{
            throw new Exception("no method for cache", 404);
        }
    }
}