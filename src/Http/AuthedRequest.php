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
 * 需要登录态的请求继承这个基础即可
 * Class AuthedRequest
 * @package ZYProSoft\Http
 */
class AuthedRequest extends Request
{
    public function rules()
    {
        return [];
    }

    protected function authorize()
    {
        return $this->isLogin();
    }
}