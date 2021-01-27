<?php


namespace ZYProSoft\Service;
use Carbon\Carbon;
use Psr\SimpleCache\CacheInterface;
use ZYProSoft\Exception\HyperfCommonException;
use ZYProSoft\Log\Log;
use Gregwar\Captcha\CaptchaBuilder;
use Hyperf\Di\Annotation\Inject;
use ZYProSoft\Constants\ErrorCode;
use ZYProSoft\Service\PublicFileService;

class CaptchaService
{
    private int $ttl = 600;

    private string $prefix = 'cpt';

    private string $dirname = '/captcha';

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

    public function setTTL(int $ttl = 600)
    {
        $this->ttl = $ttl;
    }

    public function setCachePrefix(string $prefix = 'cpt')
    {
        $this->prefix = $prefix;
    }

    public function setDirname(string $dirname)
    {
        $this->dirname = $dirname;
    }

    public function saveDir()
    {
        return $this->dirname.DIRECTORY_SEPARATOR;
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
        $cacheKey = $this->prefix.$time;
        $subDirPath = $this->subDirPath($cacheKey);
        $savePath = $this->savePath($cacheKey);
        Log::info("will save captcha path:$savePath");
        $builder->save($savePath);
        $this->cache->set($cacheKey, $phrase, $this->ttl);
        return [
            'path' => $subDirPath,
            'key' => $cacheKey,
        ];
    }

    public function validate(string $cacheKey, string $input)
    {
        $phrase = $this->cache->get($cacheKey);
        if (is_null($phrase)) {
            $savePath = $this->subDirPath($cacheKey);
            $this->publicFileService->deletePublicPath($savePath);
            throw new HyperfCommonException(ErrorCode::SYSTEM_ERROR_CAPTCHA_EXPIRED);
        }

        if ($phrase != $input) {
            throw new HyperfCommonException(ErrorCode::SYSTEM_ERROR_CAPTCHA_INVALIDATE);
        }

        $savePath = $this->subDirPath($cacheKey);
        $this->publicFileService->deletePublicPath($savePath);

        return true;
    }
}