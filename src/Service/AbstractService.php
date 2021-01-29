<?php
declare(strict_types=1);

namespace ZYProSoft\Service;
use Hyperf\Cache\Listener\DeleteListenerEvent;
use Hyperf\Filesystem\FilesystemFactory;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Qbhy\HyperfAuth\Authenticatable;
use Qbhy\HyperfAuth\AuthManager;
use Hyperf\Contract\SessionInterface;
use Psr\SimpleCache\CacheInterface;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\AsyncQueue\Driver\DriverInterface;
use Hyperf\AsyncQueue\Job;
use ZYProSoft\Cache\ClearListCacheJob;
use ZYProSoft\Cache\ClearPrefixCacheJob;
use ZYProSoft\Entry\EmailEntry;
use ZYProSoft\Job\SendEmailJob;
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

    /**
     * @var FilesystemFactory
     */
    protected $fileSystemFactory;

    /**
     * @var PublicFileService
     */
    protected $publicFileService;

    /**
     * @var EmailService
     */
    protected EmailService $emailService;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->auth = $container->get(AuthManager::class);
        $this->session = $container->get(SessionInterface::class);
        $this->cache = $container->get(CacheInterface::class);
        $this->eventDispatcher = $container->get(EventDispatcherInterface::class);
        $this->driverFactory = $container->get(DriverFactory::class);
        $this->driver = $this->driverFactory->get('default');
        $this->fileSystemFactory = $container->get(FilesystemFactory::class);
        $this->publicFileService = $container->get(PublicFileService::class);
        $this->emailService = $container->get(EmailService::class);
    }

    /**
     * 选择指定分组的队列来执行某个异步任务
     * @param string $group
     * @param Job $job
     * @param int $delay
     */
    protected function pushWithGroup(string $group, Job $job, int $delay = 0)
    {
        $driver = $this->driverFactory->get($group);
        if (!$driver) {
            Log::error("drive:$group is not exist!");
            return;
        }
        $driver->push($job, $delay);
    }

    /**
     * 使用默认分组default队列来执行任务
     * @param Job $job
     * @param int $delay
     */
    protected function push(Job $job, int $delay = 0)
    {
        $this->driver->push($job, $delay);
    }

    /**
     * 分发事件
     * @param object $event
     */
    protected function dispatch(object $event)
    {
        $this->eventDispatcher->dispatch($event);
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
     * 清除缓存
     * @param string $listener
     * @param array $arguments
     */
    protected function clearCache(string $listener, array $arguments)
    {
        $deleteEvent = new DeleteListenerEvent($listener, $arguments);
        $this->dispatch($deleteEvent);
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

    protected function fileLocal()
    {
        return $this->fileSystemFactory->get('local');
    }

    protected function fileQiniu()
    {
        return $this->fileSystemFactory->get('qiniu');
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

    protected function clearAllCache()
    {
        return $this->cache->clear();
    }

    protected function sendEmail(EmailEntry $emailEntry)
    {
        $this->emailService->sendEmail($emailEntry, false);
    }

    protected function asyncSendEmail(EmailEntry $emailEntry)
    {
        $this->push(new SendEmailJob($emailEntry));
    }
}