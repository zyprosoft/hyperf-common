<?php


namespace ZYProSoft\Cache;

use Hyperf\AsyncQueue\Job;
use Hyperf\Cache\Listener\DeleteListenerEvent;
use Hyperf\Utils\ApplicationContext;
use Psr\EventDispatcher\EventDispatcherInterface;

class ClearListCacheJob extends Job
{
    protected string $listener;

    protected array $customValues;

    protected int $pageSize;

    protected int $pageCount;

    public function __construct(string $listener, array $customValues, int $pageSize, int $maxPageCount = 15)
    {
        $this->listener = $listener;
        $this->customValues = $customValues;
        $this->pageSize = $pageSize;
        $this->pageCount = $maxPageCount;
    }

    public function handle()
    {
        //执行的时候获取dispatcher即可
        $eventDispatcher = ApplicationContext::getContainer()->get(EventDispatcherInterface::class);

        //构建缓存参数列表
        $argumentsList = [];
        for ($index = 0; $index < $this->pageCount; $index++)
        {
            $argumentItem = array_merge([$index, $this->pageSize], $this->customValues);
            $argumentsList[] = $argumentItem;
        }
        $listener = $this->listener;
        array_map(function ($argumentItem) use ($listener, $eventDispatcher) {
            $deleteEvent = new DeleteListenerEvent($listener, $argumentItem);
            $eventDispatcher->dispatch($deleteEvent);
        }, $argumentsList);
    }
}