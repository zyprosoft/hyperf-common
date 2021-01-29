<?php


namespace ZYProSoft\Service;
use Carbon\Carbon;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Filesystem\FilesystemFactory;
use Hyperf\Utils\Arr;
use Hyperf\Utils\Str;
use ZYProSoft\Log\Log;

class LogService
{
    const KEEP_LOG_FILE_FOREVER = -1;

    /**
     * @Inject
     * @var FilesystemFactory
     */
    protected FilesystemFactory $fileSystem;

    protected function keepFileDays()
    {
        return config('hyperf-common.clear_log.days');
    }

    protected function isKeepForever()
    {
        return $this->keepFileDays() == self::KEEP_LOG_FILE_FOREVER;
    }

    protected function local()
    {
        return $this->fileSystem->get('local');
    }

    protected function hasLog()
    {
        $hasDir = $this->local()->has('/logs');
        if (!$hasDir) {
            return false;
        }
        $items = $this->local()->listContents('/logs');
        if (empty($items)) {
            return false;
        }
        return true;
    }

    /**
     * 自动清理过期的日志文件
     */
    public function clearExpireLog()
    {
        if ($this->isKeepForever()) {
            Log::task("no need to clear log because setting forever");
            return;
        }

        if (!$this->hasLog()) {
            Log::task("has no log files to check clear");
            return;
        }
        $items = $this->local()->listContents('/logs');
        Log::task("start deal with log files:".json_encode($items));

        $clearPaths = [];
        array_map(function (array $file) use (&$clearPaths) {
            $path = Arr::get($file, 'path');
            if (Str::endsWith($path, '.log') == false) {
                Log::info("not a log file:$path");
                return;
            }
            $timestamp = Arr::get($file, 'timestamp');
            $lastDate = Carbon::createFromTimestamp($timestamp);
            $daysDidPass = Carbon::now()->floatDiffInRealDays($lastDate);
            Log::task("$path last modify time has been over $daysDidPass days");
            if ($daysDidPass > $this->keepFileDays()) {
                Log::task("$path need to be clear!");
                $clearPaths[] = $path;
            }
        }, $items);

        Log::task("will clear this log paths:".json_encode($clearPaths));

        array_map(function ($path) {
            $this->local()->delete($path);
        }, $clearPaths);

        Log::task("success clear expire log files!");
    }
}