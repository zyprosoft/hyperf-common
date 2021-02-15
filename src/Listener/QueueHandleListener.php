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
namespace ZYProSoft\Listener;

use Hyperf\AsyncQueue\Event\AfterHandle;
use Hyperf\AsyncQueue\Event\BeforeHandle;
use Hyperf\AsyncQueue\Event\Event;
use Hyperf\AsyncQueue\Event\FailedHandle;
use Hyperf\AsyncQueue\Event\RetryHandle;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\ExceptionHandler\Formatter\FormatterInterface;
use ZYProSoft\Log\Log;

/**
 * 异步队列处理任务时候的状态信息
 * @Listener
 */
class QueueHandleListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            AfterHandle::class,
            BeforeHandle::class,
            FailedHandle::class,
            RetryHandle::class,
        ];
    }

    public function process(object $event)
    {
        if ($event instanceof Event && $event->message->job()) {
            $job = $event->message->job();
            $jobClass = get_class($job);
            $date = date('Y-m-d H:i:s');

            switch (true) {
                case $event instanceof BeforeHandle:
                    Log::info(sprintf('[%s] Processing %s.', $date, $jobClass));
                    break;
                case $event instanceof AfterHandle:
                    Log::info(sprintf('[%s] Processed %s.', $date, $jobClass));
                    break;
                case $event instanceof FailedHandle:
                    Log::error(sprintf('[%s] Failed %s.', $date, $jobClass));
                    Log::error($event->getThrowable()->getTraceAsString());
                    break;
                case $event instanceof RetryHandle:
                    Log::warning(sprintf('[%s] Retried %s.', $date, $jobClass));
                    break;
            }
        }
    }
}
