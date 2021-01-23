<?php
declare(strict_types=1);

namespace ZYProSoft\Service;
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