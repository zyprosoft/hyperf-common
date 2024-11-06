<?php
/**
 * This file is part of ZYProSoft/Hyperf-Common.
 *
 * @link     https://topicq.icodefuture.com
 * @document https://topicq.icodefuture.com
 * @contact  1003081775@qq.com;微信:zyprosoft
 * @Company  iCodeFuture
 * @license  GPL
 */
declare(strict_types=1);

namespace ZYProSoft\Component;

use ZYProSoft\Constants\ErrorCode;
use ZYProSoft\Exception\HyperfCommonException;

/**
 * 请求第三方服务的结果，有的不是关键路径，不能抛出异常
 * 所以单独处理结果
 * Class ModuleCallResult
 * @package ZYProSoft\Component
 */
class ModuleCallResult
{
    public int $code = 0;

    public string $message = 'ok';

    public array $data = [];

    public function __construct($code = 0, $message = 'ok', $data = [])
    {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }

    public static function success($data)
    {
        return new ModuleCallResult(0, "ok", $data);
    }

    public static function fail($code, $message = 'fail', $data = [])
    {
        return new ModuleCallResult($code, $message, $data);
    }

    /**
     * 是返回成功结果还是抛出异常
     * @return array|mixed
     */
    public function successOrFailException()
    {
        if (!$this->isSuccess()) {
            throw new HyperfCommonException(ErrorCode::MODULE_CALL_FAIL, "module call fail with code({$this->code}) message({$this->message})");
        }else{
            return $this->data;
        }
    }

    public function isSuccess()
    {
        return $this->code == 0;
    }
}