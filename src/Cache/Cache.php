<?php
/**
 * This file is part of ZYProSoft/Hyperf-Common.
 *
 * @link     https://topicq.icodefuture.com
 * @document https://topicq.icodefuture.com
 * @contact  1003081775@qq.com;微信:zyprosoft
 * @Company  吉安码动未来信息科技有限公司
 * @license  GPL
 */

declare(strict_types=1);

namespace ZYProSoft\Cache;
use Hyperf\Cache\Cache as HyperfCache;

/**
 * 替换框架原来的Cache，将按照前缀清理缓存的方法暴露出来
 * Class Cache
 * @package ZYProSoft\Cache
 */
class Cache extends HyperfCache
{
    /**
     * 按指定缓存Key前缀进行清除
     * @param string $prefix
     * @return bool
     */
    public function clearPrefix(string $prefix):bool
    {
       return $this->driver->clearPrefix($prefix);
    }
}