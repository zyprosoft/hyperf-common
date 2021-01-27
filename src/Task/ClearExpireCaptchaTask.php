<?php


namespace ZYProSoft\Task;
use Hyperf\Utils\ApplicationContext;
use ZYProSoft\Service\CaptchaService;

/**
 * Class ClearExpireCaptchaTask
 * @package ZYProSoft\Task
 */
class ClearExpireCaptchaTask
{
    public function execute()
    {
        $captchaService = ApplicationContext::getContainer()->get(CaptchaService::class);
        $captchaService->clearExpireCaptcha();
    }
}