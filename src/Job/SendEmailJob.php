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
use Hyperf\AsyncQueue\Job;
use Hyperf\Utils\ApplicationContext;
use ZYProSoft\Entry\EmailEntry;
use ZYProSoft\Log\Log;
use ZYProSoft\Service\EmailService;

/**
 * 异步发送邮件的任务
 * Class SendEmailJob
 * @package ZYProSoft\Job
 */
class SendEmailJob extends Job
{
    private EmailEntry $emailEntry;

    /**
     * 最大重试次数
     * @var int
     */
    protected $maxAttempts = 3;

    public function __construct(EmailEntry $emailEntry)
    {
        $this->emailEntry = $emailEntry;
    }

    /**
     * @inheritDoc
     */
    public function handle()
    {
        Log::info("begin process send email task:".json_encode($this->emailEntry));
        $service = ApplicationContext::getContainer()->get(EmailService::class);
        $service->sendEmail($this->emailEntry);
        Log::info("async success send email:".json_encode($this->emailEntry));
    }
}