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