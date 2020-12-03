<?php


namespace ZYProSoft\Middleware;
use Hyperf\Validation\Middleware\ValidationMiddleware;
use Hyperf\Validation\UnauthorizedException;
use Psr\Http\Message\ResponseInterface;

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