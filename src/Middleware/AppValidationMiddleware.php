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

namespace ZYProSoft\Middleware;
use Hyperf\Validation\Middleware\ValidationMiddleware;
use Hyperf\Validation\UnauthorizedException;
use Psr\Http\Message\ResponseInterface;

/**
 * 参数合法性检查插件
 * Class AppValidationMiddleware
 * @package ZYProSoft\Middleware
 */
class AppValidationMiddleware extends ValidationMiddleware
{
    /**
     * 重写这个行为,不要变成响应回去,抛出异常，让异常捕捉器去处理
     * @param UnauthorizedException $exception
     * @return ResponseInterface
     */
    protected function handleUnauthorizedException(UnauthorizedException $exception): ResponseInterface
    {
         throw $exception;
    }
}