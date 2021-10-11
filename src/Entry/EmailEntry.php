<?php
/**
 * This file is part of ZYProSoft/Hyperf-Common.
 *
 * @link     https://topicq.icodefuture.com
 * @document https://topicq.icodefuture.com
 * @contact  1003081775@qq.com;微信:zyprosoft
 * @Company  吉安码动未来信息科技有限公司
 * @license  GPL
 */
declare(strict_types=1);

namespace ZYProSoft\Entry;

/**
 * 一封邮件的实体代表信息
 * Class EmailEntry
 * @package ZYProSoft\Entry
 */
class EmailEntry
{
    public EmailAddressEntry $from;

    public array $receivers = [];//内容是EmailAddressEntry

    public EmailAddressEntry $replyTo;//EmailAddressEntry

    public array $ccReceivers = [];//抄送列表,内容是EmailAddressEntry

    public array $bccReceivers = [];//密送列表,内容是EmailAddressEntry

    public array $attachments = [];//附件信息,内容是EmailAttachmentEntry

    public bool $isHtml = true;//是不是html内容

    public string $subject;//邮件主题

    public string $body;//邮件内容

    public string $altBody;//当邮件客户端不支持Html时候的备用显示内容

    public function isValidate()
    {
        if (!isset($this->from)) {
            return false;
        }

        if (!empty($this->receivers)) {
            return false;
        }

        if (!isset($this->subject) || !isset($this->body)) {
            return false;
        }

        return true;
    }
}