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

namespace ZYProSoft\Controller;
use Hyperf\HttpServer\Annotation\AutoController;

/**
 * 基础的验证码服务的集成
 * @AutoController (prefix="/common/captcha")
 * Class CaptchaController
 * @package App\Controller\Common
 */
class CaptchaController extends AbstractController
{
    /**
     * 获取一条验证码
     * 此接口访问名为common.captcha.get
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function get()
    {
        return $this->success($this->captchaService->get());
    }

    /**
     * 刷新一条验证码
     * 此接口访问名为common.captcha.refresh
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function refresh()
    {
        $this->validate([
            'key' => 'string|min:1'
        ]);
        $cacheKey = $this->request->param('key');
        return $this->success($this->captchaService->refresh($cacheKey));
    }
}