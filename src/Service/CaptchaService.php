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
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\Utils\Arr;
use Hyperf\Utils\Str;
use Psr\SimpleCache\CacheInterface;
use ZYProSoft\Exception\HyperfCommonException;
use Gregwar\Captcha\CaptchaBuilder;
use Hyperf\Di\Annotation\Inject;
use ZYProSoft\Constants\ErrorCode;
use ZYProSoft\Log\Log;
use ZYProSoft\Job\ClearCaptchaJob;

/**
 * 验证码服务
 * Class CaptchaService
 * @package ZYProSoft\Service
 */
class CaptchaService
{
    const DIR_NAME_CURRENT = '.';

    const DIR_NAME_LAST_LEVEL = '..';

    /**
     * @Inject
     * @var PublicFileService
     */
    private PublicFileService $publicFileService;

    /**
     * @Inject
     * @var CacheInterface
     */
    private CacheInterface $cache;

    /**
     * @Inject
     * @var DriverFactory
     */
    protected DriverFactory $driverFactory;

    private function ttl()
    {
        return config('hyperf-common.captcha.ttl');
    }

    private function prefix()
    {
        return config('hyperf-common.captcha.prefix');
    }

    private function dirname()
    {
        return config('hyperf-common.captcha.dirname');
    }

    protected function driver()
    {
        return $this->driverFactory->get('default');
    }

    protected function saveDir()
    {
        return $this->dirname().DIRECTORY_SEPARATOR;
    }

    public function subDirPath($cacheKey)
    {
        $result = $this->publicFileService->createPublicSubDirIfNotExist($this->saveDir());
        if (!$result) {
            return  null;
        }
        return $this->saveDir().$cacheKey.'.jpeg';
    }

    public function savePath($cacheKey)
    {
        return $this->publicFileService->publicPath($this->subDirPath($cacheKey));
    }

    /**
     * 获取一张验证码图片和信息
     * @return string[]
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function get()
    {
        $builder = new CaptchaBuilder();
        $builder->build();
        $phrase = $builder->getPhrase();
        $time = Carbon::now()->timestamp;
        $cacheKey = $this->prefix().$time;
        $subDirPath = $this->subDirPath($cacheKey);
        $savePath = $this->savePath($cacheKey);
        $builder->save($savePath);
        $this->cache->set($cacheKey, $phrase, $this->ttl());
        $urlPrefix = config('hyperf-common.upload.local.url_prefix');
        $urlPrefix = rtrim($urlPrefix,'/');
        return [
            'url' => $urlPrefix.DIRECTORY_SEPARATOR.ltrim($subDirPath,'/'),
            'key' => $cacheKey,
        ];
    }

    /**
     * 校验提交的验证码是否正确
     * @param string $cacheKey
     * @param string $input
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function validate(string $cacheKey, string $input)
    {
        $phrase = $this->cache->get($cacheKey);
        if (is_null($phrase)) {
            $this->asyncClear($cacheKey);
            throw new HyperfCommonException(ErrorCode::SYSTEM_ERROR_CAPTCHA_EXPIRED);
        }

        Log::info("input:$input phrase:$phrase");

        $isStrictMode = config('hyperf-common.captcha.strict');

        if ($isStrictMode && $phrase !== $input) {
            throw new HyperfCommonException(ErrorCode::SYSTEM_ERROR_CAPTCHA_INVALIDATE);
        }

        if (Str::lower($phrase) !== Str::lower($input)) {
            throw new HyperfCommonException(ErrorCode::SYSTEM_ERROR_CAPTCHA_INVALIDATE);
        }

        $this->asyncClear($cacheKey);

        return true;
    }

    /**
     * 异步清除指定的验证码信息
     * @param string $cacheKey
     */
    public function asyncClear(string $cacheKey)
    {
        $this->driver()->push(new ClearCaptchaJob($cacheKey));
    }

    /**
     * 删除验证码图片
     * @param string $cacheKey
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function remove(string $cacheKey)
    {
        $savePath = $this->subDirPath($cacheKey);
        $this->publicFileService->deletePublicPath($savePath);
        $this->cache->delete($cacheKey);
    }

    /**
     * 刷新验证码
     * @param string|null $cacheKey
     * @return string[]
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function refresh(string $cacheKey = null)
    {
        if (!isset($cacheKey)) {
            return $this->get();
        }
        $phrase = $this->cache->get($cacheKey);
        if (!isset($phrase)) {
            return $this->get();
        }
        $this->asyncClear($cacheKey);
        return  $this->get();
    }

    /**
     * 清理过期的验证码图片和缓存
     * 通常都在异步任务里面执行
     */
    public function clearExpireCaptcha()
    {
        $captchaDir = $this->publicFileService->publicPath($this->saveDir());
        if(!file_exists($captchaDir)) {
           $result = mkdir($captchaDir,0755,true);
           if(!$result) {
               Log::error("创建验证码目录失败!");
               return;
           }
        }
        $files = scandir($captchaDir);
        if (empty($files)) {
            Log::task("no captcha file to check expire!");
            return;
        }
        Log::task("will check captcha files:".json_encode($files));

        $expireKeys = [];
        array_map(function (string $filename) use (&$expireKeys) {
            if ($filename == self::DIR_NAME_CURRENT || $filename == self::DIR_NAME_LAST_LEVEL) {
                Log::task("no need deal system file name :$filename");
                return;
            }
            $name = Arr::first(explode('.', $filename));
            $timestamp = Str::after($name, $this->prefix());
            $date = Carbon::createFromTimestamp($timestamp);
            Log::task("get an captcha file time:".$date->toString());
            $secondsDidPass = Carbon::now()->diffInRealSeconds($date);
            Log::task("$filename has been created $secondsDidPass seconds");
            if ($secondsDidPass > $this->ttl()) {
                $expireKeys[] = $timestamp;
            }
        }, $files);
        Log::task("will clear expire captcha keys:".json_encode($expireKeys));

        array_map(function (string $expireKey) {
            $cacheKey = $this->prefix().$expireKey;
            $this->remove($cacheKey);
        }, $expireKeys);
        Log::task("success clear expire captcha!");
    }
}