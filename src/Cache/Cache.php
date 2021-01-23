<?php
declare(strict_types=1);

namespace ZYProSoft\Cache;
use Hyperf\Cache\Cache as HyperfCache;

class Cache extends HyperfCache
{
    public function clearPrefix(string $prefix):bool
    {
       return $this->driver->clearPrefix($prefix);
    }
}