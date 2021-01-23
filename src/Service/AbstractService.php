<?php
declare(strict_types=1);

namespace ZYProSoft\Service;
use Hyperf\Cache\Listener\DeleteListenerEvent;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Qbhy\HyperfAuth\Authenticatable;
use Qbhy\HyperfAuth\AuthManager;
use Hyperf\Contract\SessionInterface;
use Psr\SimpleCache\CacheInterface;
use ZYProSoft\Cache\Cache;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\AsyncQueue\Driver\DriverInterface;
use Hyperf\AsyncQueue\Job;
use ZYProSoft\Cache\ClearListCacheJob;
use ZYProSoft\Cache\ClearPrefixCacheJob;
use ZYProSoft\Log\Log;

abstract class AbstractService
{
    /**
     * @var ContainerInterface
     */
    protected ContainerInterface $container;

    /**
     * @var AuthManager
     */
    protected $auth;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var DriverInterface
     */
    protected $driver;

    /**
     * @var DriverFactory
     */
    protected $driverFactory;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->auth = $container->get(AuthManager::class);
        $this->session = $container->get(SessionInterface::class);
        $this->cache = $container->get(CacheInterface::class);
        $this->eventDispatcher = $container->get(EventDispatcherInterface::class);
        $this->driverFactory = $container->get(DriverFactory::class);
        $this->driver = $this->driverFactory->get('default');
    }

    public function pushWithGroup(string $group, Job $job, int $delay = 0)
    {
        $driver = $this->driverFactory->get($group);
        if (!$driver) {
            Log::error("drive:$group is not exist!");
            return;
        }
        $driver->push($job, $delay);
    }

    public function push(Job $job, int $delay = 0)
    {
        $this->driver->push($job, $delay);
    }

    /**
     * 通过异步任务清除缓存
     * @param string $prefix
     */
    protected function clearCachePrefix(string $prefix)
    {
        $this->push(new ClearPrefixCacheJob($prefix));
    }

    /**
     * 设计的缓存列表接口参数形式必须保证为(int $pageIndex, int $pageSize, ...$customValues)
     * 否则无法通过这种形式删除列表类型的缓存
     * @param string $listener
     * @param array $customValues
     * @param int $pageSize
     * @param int $maxPageCount
     */
    protected function clearListCacheWithMaxPage(string $listener, array $customValues, int $pageSize, int $maxPageCount = 15)
    {
        $this->push(new ClearListCacheJob($listener, $customValues, $pageSize, $maxPageCount));
    }

    protected function userId()
    {
        return $this->user()->getId();
    }

    protected function user():Authenticatable
    {
        return $this->auth->user();
    }

    protected function success($data = [])
    {
        return $data;
    }
}