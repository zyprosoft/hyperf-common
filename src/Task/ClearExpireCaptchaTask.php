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

namespace ZYProSoft\Task;
use Hyperf\Utils\ApplicationContext;
use ZYProSoft\Service\CaptchaService;

/**
 * 定时清理过期的验证码文件
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