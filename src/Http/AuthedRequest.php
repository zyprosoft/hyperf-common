<?php


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