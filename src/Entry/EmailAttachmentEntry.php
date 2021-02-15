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