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

namespace ZYProSoft\Job;
use \Hyperf\AsyncQueue\Job;
use Hyperf\Utils\ApplicationContext;
use ZYProSoft\Log\Log;
use ZYProSoft\Service\CaptchaService;

/**
 * 清除验证码信息的异步任务
 * Class ClearCaptchaJob
 * @package ZYProSoft\Job
 */
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