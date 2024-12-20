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

namespace ZYProSoft\Service;
use Carbon\Carbon;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Filesystem\FilesystemFactory;
use Hyperf\Utils\Str;
use ZYProSoft\Log\Log;
use League\Flysystem\FileAttributes;

/**
 * 日志服务主要用于清理日志文件时用
 * Class LogService
 * @package ZYProSoft\Service
 */
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
        $items = collect($this->local()->listContents('/logs'));
        $items->filter(function (FileAttributes $item) {
            $systemFiles = ['.','..'];
            $pathArray = explode("/",$item->path());
            $filename = "";
            if ( !empty($pathArray)) {
                $filename = $pathArray[count($pathArray) - 1];
            }
            return !in_array($filename, $systemFiles);
        });
        if ($items->isEmpty()) {
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
        $items = collect($this->local()->listContents('/logs'));
        Log::task("start deal with log files:".json_encode($items));

        $clearPaths = [];
        $items->map(function (FileAttributes $file) use (&$clearPaths) {
            $path = $file->path();
            if (Str::endsWith($path, '.log') == false) {
                Log::info("not a log file:$path");
                return;
            }
            $timestamp = $file->lastModified();
            $lastDate = Carbon::createFromTimestamp($timestamp);
            $daysDidPass = Carbon::now()->floatDiffInRealDays($lastDate);
            Log::task("$path last modify time has been over $daysDidPass days");
            if ($daysDidPass > $this->keepFileDays()) {
                Log::task("$path need to be clear!");
                $clearPaths[] = $path;
            }
        });

        Log::task("will clear this log paths:".json_encode($clearPaths));

        array_map(function ($path) {
            $this->local()->delete($path);
        }, $clearPaths);

        Log::task("success clear expire log files!");
    }
}