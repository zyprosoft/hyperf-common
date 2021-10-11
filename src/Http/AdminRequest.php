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