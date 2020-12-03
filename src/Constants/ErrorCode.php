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
namespace ZYProSoft\Constants;

use Hyperf\Constants\AbstractConstants;
use Hyperf\Constants\Annotation\Constants;

/**
 * 错误码分层
 *
 * @Constants
 */
class ErrorCode extends AbstractConstants
{
    /**
     * @Message("Server Error！")
     */
    const SERVER_ERROR = 500;

    /**
     * @Message("param validate fail!")
     */
    const PARAM_ERROR = 9999;

    /**
     * @Message("DB error!")
     */
    const DB_ERROR = 9998;

    /**
     * @Message("ZGW request body error!")
     */
    const ZGW_REQUEST_BODY_ERROR = 9997;

    /**
     * @Message("ZGW request auth appId not exist!")
     */
    const ZGW_AUTH_APP_ID_NOT_EXIST = 9996;

    /**
     * @Message("ZGW request auth signature error!")
     */
    const ZGW_AUTH_SIGNATURE_ERROR = 9995;

    /**
     * @Message("Request rate reach limit!")
     */
    const REQUEST_RATE_LIMIT = 9994;

    /**
     * @Message("Record not exist!")
     */
    const RECORD_NOT_EXIST = 10000;

    /**
     * @Message("Token not validate!")
     */
    const TOKEN_NOT_VALIDATE = 10001;

    /**
     * @Message("Record did exist!")
     */
    const RECORD_DID_EXIST = 10002;

    /**
     * @Message("Logout fail!")
     */
    const LOGOUT_FAIL = 10003;

    /**
     * @Message("Auth fail!")
     */
    const AUTH_FAIL = 10004;

    /**
     * @Message("User not approved!")
     */
    const USER_NOT_APPROVED = 10005;

    /**
     * @Message("Update record fail!")
     */
    const UPDATE_RECORD_FAIL = 10006;

    /**
     * @Message("Action need admin role!")
     */
    const ACTION_REQUIRE_ADMIN = 10008;

    /**
     * @Message("Module call fail!")
     */
    const MODULE_CALL_FAIL = 10011;

    /**
     * @Message("USER HAS NO PERMISSION DO THIS ACTION!")
     */
    const PERMISSION_ERROR = 10012;
}
