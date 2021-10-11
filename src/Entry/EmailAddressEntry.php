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

use Hyperf\Utils\Str;

/**
 * 邮件地址实体信息
 * Class EmailAddressEntry
 * @package ZYProSoft\Entry
 */
class EmailAddressEntry
{
    /**
     * 地址，类似:1003081775@qq.com;微信:zyprosoft
     * @var string
     */
    public string $address;

    /**
     * 地址对应的名字
     * @var string
     */
    public string $name;

    public function __construct(string $address, string $name)
    {
        $this->address = $address;
        $this->name = $name;
    }
}