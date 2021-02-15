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
namespace ZYProSoft\Http;


/**
 * 管理员请求，可以通过重载
 * isAdmin()方式来决定是否管理员
 * Class AdminRequest
 * @package ZYProSoft\Http
 */
class AdminRequest extends AuthedRequest
{
    public function rules()
    {
        return [];
    }

    protected function authorize()
    {
        return $this->isAdmin();
    }
}