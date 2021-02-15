<?php
/**
 * This file is part of ZYProSoft/Hyperf-Common.
 *
 * @link     http://zyprosoft.lulinggushi.com
 * @document http://zyprosoft.lulinggushi.com
 * @contact  1003081775@qq.com
 * @Company  泽湾普罗信息技术有限公司(ZYProSoft)
 * @license  GPL
 */
declare(strict_types=1);

namespace ZYProSoft\Service;


use ZYProSoft\Entry\EmailAddressEntry;
use ZYProSoft\Entry\EmailAttachmentEntry;
use ZYProSoft\Entry\EmailEntry;
use ZYProSoft\Log\Log;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * 邮件发送服务
 * Class EmailService
 * @package ZYProSoft\Service
 */
class EmailService
{
    protected function mailer()
    {
        $mail = new PHPMailer(true);
        $config = config('hyperf-common.mail.smtp');
        $mail->SMTPDebug = SMTP::DEBUG_OFF;                      // Enable verbose debug output
        $mail->isSMTP();                                            // Send using SMTP
        $mail->Host       = $config['host'];                    // Set the SMTP server to send through
        $mail->SMTPAuth   = $config['auth'];                                   // Enable SMTP authentication
        $mail->Username   = $config['username'];                     // SMTP username
        $mail->Password   = $config['password'];                               // SMTP password
        $mail->SMTPSecure = $config['secure'];         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
        $mail->Port       = $config['port'];
        return $mail;
    }

    /**
     * 发送邮件，是直接发，还是在异步任务内发
     * 可以将相应的日志写入对应的日志文件
     * @param EmailEntry $emailEntry
     * @param bool $isInTask
     * @return bool
     */
    public function sendEmail(EmailEntry $emailEntry, bool $isInTask = true)
    {
        $logger = Log::logger('task');
        if (!$isInTask) {
            $logger = Log::logger('default');
        }

        if (!$emailEntry || !$emailEntry->isValidate()) {
            $logger->error("email entry is not validate to send!");
            return false;
        }

        $mail = $this->mailer();
        try{
            $mail->setFrom($emailEntry->from->address, $emailEntry->from->name);
            if (!empty($emailEntry->receivers)) {
                array_map(function (EmailAddressEntry $address) use ($mail) {
                    $mail->addAddress($address->address,$address->name);
                }, $emailEntry->receivers);
            }
            if (isset($emailEntry->replyTo)) {
                $mail->addReplyTo($emailEntry->replyTo->address,$emailEntry->replyTo->name);
            }
            if (isset($emailEntry->ccReceivers)) {
                array_map(function (EmailAddressEntry $address) use ($mail) {
                    $mail->addCC($address->address,$address->name);
                }, $emailEntry->ccReceivers);
            }
            if (isset($emailEntry->bccReceivers)) {
                array_map(function (EmailAddressEntry $address) use ($mail) {
                    $mail->addBCC($address->address,$address->name);
                }, $emailEntry->bccReceivers);
            }
            if (isset($emailEntry->attachments)) {
                array_map(function (EmailAttachmentEntry $attachment) use ($mail) {
                    $mail->addAttachment($attachment->path, $attachment->name);
                }, $emailEntry->attachments);
            }
            $mail->isHTML($emailEntry->isHtml);
            $mail->Subject = $emailEntry->subject;
            $mail->Body = $emailEntry->body;
            if (isset($emailEntry->altBody)) {
                $mail->AltBody = $emailEntry->altBody;
            }
            $logger->info("did send email with info:".json_encode($emailEntry));
            return $mail->send();
        }catch (Exception $exception) {
            $code = $exception->getCode();
            $message = $exception->getMessage();
            $trace = $exception->getTraceAsString();
            $logger->error("send email error code:$code message:$message");
            $logger->error($trace);
            return false;
        }
    }
}