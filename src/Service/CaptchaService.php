<?php


namespace ZYProSoft\Service;
use Carbon\Carbon;
use Psr\SimpleCache\CacheInterface;
use ZYProSoft\Exception\HyperfCommonException;
use Gregwar\Captcha\CaptchaBuilder;
use Hyperf\Di\Annotation\Inject;
use ZYProSoft\Constants\ErrorCode;
use ZYProSoft\Log\Log;

class CaptchaService
{
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
        return $this->saveDir().$cacheKey;
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
        return [
            'path' => $subDirPath,
            'key' => $cacheKey,
        ];
    }

    public function validate(string $cacheKey, string $input)
    {
        $phrase = $this->cache->get($cacheKey);
        if (is_null($phrase)) {
            $this->remove($cacheKey);
            throw new HyperfCommonException(ErrorCode::SYSTEM_ERROR_CAPTCHA_EXPIRED);
        }

        if ($phrase != $input) {
            throw new HyperfCommonException(ErrorCode::SYSTEM_ERROR_CAPTCHA_INVALIDATE);
        }

        $this->remove($cacheKey);

        return true;
    }

    private function remove(string $cacheKey)
    {
        $savePath = $this->subDirPath($cacheKey);
        $this->publicFileService->deletePublicPath($savePath);
        $this->cache->delete($cacheKey);
    }

    public function refresh(string $cacheKey)
    {
        $phrase = $this->cache->get($cacheKey);
        if (is_null($phrase)) {
            return $this->get();
        }
        $this->remove($cacheKey);
        return  $this->get();
    }

    public function clearExpireCaptcha()
    {
        $files = scandir($this->publicFileService->publicPath($this->saveDir()));
        if (empty($files)) {
            Log::info("no captcha file to check expire!");
            return;
        }
        Log::info("will check captcha files:".json_encode($files));

        $expireKeys = [];
        array_map(function (string $filename) use ($expireKeys) {
            $timestamp = substr($filename,strlen($this->prefix()));
            $date = date('Y-m-d H:i:s', $timestamp);
            if (Carbon::now()->diffInRealSeconds($date) > $this->ttl()) {
                $expireKeys[] = $timestamp;
            }
        }, $files);
        Log::info("will clear expire captcha keys:".json_encode($expireKeys));

        array_map(function (string $expireKey) {
            $cacheKey = $this->prefix().$expireKey;
            $this->remove($cacheKey);
        }, $expireKeys);
        Log::info("success clear expire captcha!");
    }
}