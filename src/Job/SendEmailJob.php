<?php


namespace ZYProSoft\Job;
use Hyperf\AsyncQueue\Job;
use Hyperf\Utils\ApplicationContext;
use ZYProSoft\Entry\EmailEntry;
use ZYProSoft\Log\Log;
use ZYProSoft\Service\EmailService;

class SendEmailJob extends Job
{
    private EmailEntry $emailEntry;

    public function __construct(EmailEntry $emailEntry)
    {
        $this->emailEntry = $emailEntry;
    }

    /**
     * @inheritDoc
     */
    public function handle()
    {
        $service = ApplicationContext::getContainer()->get(EmailService::class);
        $service->sendEmail($this->emailEntry);
        Log::info("async success send email:".json_encode($this->emailEntry));
    }
}