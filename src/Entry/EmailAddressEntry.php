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

use Hyperf\Utils\Str;

/**
 * 邮件地址实体信息
 * Class EmailAddressEntry
 * @package ZYProSoft\Entry
 */
class EmailAddressEntry
{
    /**
     * 地址，类似:1003081775@qq.com
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