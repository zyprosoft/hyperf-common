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

namespace ZYProSoft\Entry;

/**
 * 邮件附件实体信息
 * Class EmailAttachmentEntry
 * @package ZYProSoft\Entry
 */
class EmailAttachmentEntry
{
    public string $path;

    public string $name = '';

    public function __construct(string $path, string $name)
    {
        $this->path = $path;
        $this->name = $name;
    }
}