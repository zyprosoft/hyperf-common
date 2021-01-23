<?php


namespace ZYProSoft\Cache;

use Hyperf\AsyncQueue\Job;
use Hyperf\Utils\ApplicationContext;
use Psr\SimpleCache\CacheInterface;
use ZYProSoft\Cache\Cache;
use ZYProSoft\Log\Log;

class ClearPrefixCacheJob extends Job
{
    private string $prefix;

    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
    }

    public function handle()
    {
        $cache = ApplicationContext::getContainer()->get(CacheInterface::class);
        if ($cache instanceof Cache) {
            Log::info("clear prefix cache async!");
            $cache->clearPrefix($this->prefix);
        }
    }
}