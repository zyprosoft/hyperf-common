<?php


namespace ZYProSoft\Job;
use \Hyperf\AsyncQueue\Job;
use Hyperf\Utils\ApplicationContext;
use ZYProSoft\Log\Log;
use ZYProSoft\Service\CaptchaService;

class ClearCaptchaJob extends Job
{
    private string $cacheKey;

    public function __construct(string $cacheKey)
    {
        $this->cacheKey = $cacheKey;
    }

    /**
     * @inheritDoc
     */
    public function handle()
    {
        $captchaService = ApplicationContext::getContainer()->get(CaptchaService::class);
        $captchaService->remove($this->cacheKey);
        Log::info("async clear captcha success with key:".$this->cacheKey);
    }
}