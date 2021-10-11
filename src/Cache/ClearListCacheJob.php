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

use Hyperf\AsyncQueue\Job;
use Hyperf\Cache\Listener\DeleteListenerEvent;
use Hyperf\Utils\ApplicationContext;
use Psr\EventDispatcher\EventDispatcherInterface;
use ZYProSoft\Log\Log;

/**
 * 清理列表类型缓存的异步任务
 * Class ClearListCacheJob
 * @package ZYProSoft\Cache
 */
class ClearListCacheJob extends Job
{
    /**
     * 删除缓存监听的名称
     * @var string
     */
    protected string $listener;

    /**
     * 获取列表时候的自定义参数
     * @var array
     */
    protected array $customValues;

    /**
     * 获取列表时候的页面大小
     * @var int
     */
    protected int $pageSize;

    /**
     * 获取列表时候的页面总数
     * @var int
     */
    protected int $pageCount;

    public function __construct(string $listener, array $customValues, int $pageSize, int $maxPageCount = 15)
    {
        $this->listener = $listener;
        $this->customValues = $customValues;
        $this->pageSize = $pageSize;
        $this->pageCount = $maxPageCount;
    }

    /**
     * 当异步任务被处理时候执行的动作
     * 主要就是按照缓存参数列表，构建清除缓存的条件进行缓存清除
     */
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
        Log::info('will dispatch clear list cache with listener:'.$listener.' with arguments list:'.json_encode($argumentsList));
        array_map(function ($argumentItem) use ($listener, $eventDispatcher) {
            Log::info("each delete item argument:".json_encode($argumentItem));
            $deleteEvent = new DeleteListenerEvent($listener, $argumentItem);
            Log::info('will dispatch clear list cache with event:'.json_encode($deleteEvent));
            $eventDispatcher->dispatch($deleteEvent);
        }, $argumentsList);
    }
}