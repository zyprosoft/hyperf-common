<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace ZYProSoft\Exception\Handler;

use Hyperf\Server\Exception\ServerException;
use Hyperf\Validation\UnauthorizedException;
use Psr\Container\ContainerInterface;
use ZYProSoft\Constants\ErrorCode;
use ZYProSoft\Exception\HyperfCommonException;
use ZYProSoft\Log\Log;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Exception\HttpException;
use Hyperf\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Qbhy\HyperfAuth\Exception\AuthException;
use Throwable;
use ZYProSoft\Http\Response;

class HyperfCommonExceptionHandler extends ExceptionHandler
{

    /**
     * @var Response
     */
    private $response;

    public function __construct(ContainerInterface $container)
    {
        $this->response = $container->get(Response::class);
    }

    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $this->stopPropagation();

        Log::error("exception code: ".$throwable->getCode());

        //记录错误堆栈
        $trace = $throwable->getTraceAsString();
        Log::error($trace);
        Log::req($trace);

        if ($throwable instanceof HyperfCommonException) {
            Log::error("hyperf common exception did get");

            return $this->response->fail($throwable->getCode(), $throwable->getMessage());
        }

        if ($throwable instanceof ValidationException) {
            $convertErrors = [];
            $outPutParams = [];
            foreach ($throwable->errors() as $paramName => $errors)
            {
                $errorMsg = $paramName." error description is: ".implode('、', $errors);
                $convertErrors[] = $errorMsg;
                $outPutParams[] = $paramName;
            }
            $errorMsg = "param validate error: ".implode(';', $convertErrors);
            Log::error($errorMsg);
            $errorMsg = implode(',',$outPutParams).'参数出现错误';
            return $this->response->fail(ErrorCode::PARAM_ERROR, $errorMsg);
        }

        if ($throwable instanceof AuthException) {
            Log::error("auth fail: ".$throwable->getMessage());

            return $this->response->fail(ErrorCode::AUTH_FAIL, "auth fail!");
        }

        if ($throwable instanceof UnauthorizedException) {
            $errorMsg = "user have no permission do this action or have no token in request!";
            Log::error($errorMsg);

            return $this->response->fail(ErrorCode::PERMISSION_ERROR, $errorMsg);
        }

        if ($throwable instanceof ServerException) {
            Log::error("server exception did get");
            return $this->response->fail(ErrorCode::SERVER_ERROR, $throwable->getMessage());
        }

        if ($throwable instanceof HttpException) {
            $code = $throwable->getStatusCode();
            $errorMsg = $throwable->getMessage();
        }else{
            $code = ErrorCode::SERVER_ERROR;
            $originErrorMsg = $throwable->getMessage();
            $originErrorCode = $throwable->getCode();
            Log::error('origin error code: '.$originErrorCode.' origin error message: '.$originErrorMsg);
            $errorMsg = "Server got an bad internal error!";
        }

        //打印致命错误信息
        $logMsg = "throw exception with code: ".$code." detail: ".$errorMsg;
        Log::error($logMsg);
        Log::req($logMsg);

        return $this->response->fail($code, $errorMsg);
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}
