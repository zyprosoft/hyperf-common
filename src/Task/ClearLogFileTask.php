<?php


namespace ZYProSoft\Task;

use Hyperf\Utils\ApplicationContext;
use ZYProSoft\Log\Log;
use ZYProSoft\Service\LogService;

/**
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