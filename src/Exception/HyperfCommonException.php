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
namespace ZYProSoft\Exception;

use ZYProSoft\Constants\ErrorCode;
use Hyperf\Server\Exception\ServerException;
use Throwable;
use App\Constants\ErrorCode as BusinessErrorCode;

/**
 * 框架异常
 * Class HyperfCommonException
 * @package ZYProSoft\Exception
 */
class HyperfCommonException extends ServerException
{
    public function __construct(int $code = 0, string $message = null, Throwable $previous = null)
    {
        if (!isset($message)) {
            $message = BusinessErrorCode::getMessage($code);
        }
        if(empty($message)) {
            $message = ErrorCode::getMessage($code);
        }

        parent::__construct($message, $code, $previous);
    }
}
