<?php
/**
 * This file is part of ZYProSoft/Hyperf-Common.
 *
 * @link     http://zyprosoft.lulinggushi.com
 * @document http://zyprosoft.lulinggushi.com
 * @contact  1003081775@qq.com
 * @Company  泽湾普罗信息技术有限公司(ZYProSoft)
 * @license  GPL
 */
declare(strict_types=1);

namespace ZYProSoft\Cache;

use Hyperf\AsyncQueue\Job;
use Hyperf\Utils\ApplicationContext;
use Psr\SimpleCache\CacheInterface;
use ZYProSoft\Log\Log;

/**
 * 按照缓存key前缀进行缓存清理的任务
 * Class ClearPrefixCacheJob
 * @package ZYProSoft\Cache
 */
class ClearPrefixCacheJob extends Job
{
    /**
     * 缓存的前缀
     * @var string
     */
    private string $prefix;

    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * 异步任务执行的时候，调用系统的Cache组件清理缓存
     */
    public function handle()
    {
        $cache = ApplicationContext::getContainer()->get(CacheInterface::class);
        if ($cache instanceof Cache) {
            Log::info("clear prefix cache async!");
            $cache->clearPrefix($this->prefix);
        }
    }
}