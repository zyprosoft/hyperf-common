<?php
/**
 * This file is part of ZYProSoft/Hyperf-Common.
 *
 * @link     https://topicq.icodefuture.com
 * @document https://topicq.icodefuture.com
 * @contact  1003081775@qq.com;微信:zyprosoft
 * @Company  iCodeFuture
 * @license  GPL
 */
declare(strict_types=1);

namespace ZYProSoft\Task;

use Hyperf\Utils\ApplicationContext;
use ZYProSoft\Log\Log;
use ZYProSoft\Service\LogService;

/**
 * 定时清理过期的日志文件
 * Class ClearLogFileTask
 * @package ZYProSoft\Task
 */
class ClearLogFileTask
{
    public function execute()
    {
        $service = ApplicationContext::getContainer()->get(LogService::class);
        try {
            $service->clearExpireLog();
        }catch (\Throwable $exception) {
            $code = $exception->getCode();
            $message = $exception->getMessage();
            $backTrace = $exception->getTraceAsString();
            Log::task("clear log task exception:$code message:$message");
            Log::task($backTrace);
        }
    }
}