<?php


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

    public function validate(string $cacheKey, string $input)
    {
        $phrase = $this->cache->get($cacheKey);
        if (is_null($phrase)) {
            $this->asyncClear($cacheKey);
            throw new HyperfCommonException(ErrorCode::SYSTEM_ERROR_CAPTCHA_EXPIRED);
        }

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

    public function asyncClear(string $cacheKey)
    {
        $this->driver()->push(new ClearCaptchaJob($cacheKey));
    }

    public function remove(string $cacheKey)
    {
        $savePath = $this->subDirPath($cacheKey);
        $this->publicFileService->deletePublicPath($savePath);
        $this->cache->delete($cacheKey);
    }

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

    public function clearExpireCaptcha()
    {
        $files = scandir($this->publicFileService->publicPath($this->saveDir()));
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