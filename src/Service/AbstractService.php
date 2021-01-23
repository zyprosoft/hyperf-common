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

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->auth = $container->get(AuthManager::class);
        $this->session = $container->get(SessionInterface::class);
        $this->cache = $container->get(CacheInterface::class);
        $this->eventDispatcher = $container->get(EventDispatcherInterface::class);
    }

    protected function clearCachePrefix(string $prefix)
    {
        if ($this->cache instanceof Cache) {
            $this->cache->clearPrefix($prefix);
        }
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
        //构建缓存参数列表
        $argumentsList = [];
        for ($index = 0; $index < $maxPageCount; $index++)
        {
            $argumentItem = array_merge([$index, $pageSize], $customValues);
            $argumentsList[] = $argumentItem;
        }
        array_map(function ($argumentItem) use ($listener) {
            $deleteEvent = new DeleteListenerEvent($listener, $argumentItem);
            $this->eventDispatcher->dispatch($deleteEvent);
        }, $argumentsList);
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