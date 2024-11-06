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

namespace ZYProSoft\Constants;


class Constants
{
    //用户是管理员角色
    const USER_ROLE_ADMIN = 1;

    //框架运行时设置ZGW协议标示
    const ZYPROSOFT_ZGW = "ZYProSoft-ZGW";

    //框架请求处理时设置请求唯一标记
    const ZYPROSOFT_REQ_ID = "ZYProSoft-ReqId";

    //框架请求处理识别为上传请求的处理
    const ZYPROSOFT_UPLOAD = "ZYProSoft-Upload";

    //上传文件系统使用本地标记
    const UPLOAD_SYSTEM_TYPE_LOCAL = 'local';

    //上传文件系统使用七牛标记
    const UPLOAD_SYSTEM_TYPE_QINIU = 'qiniu';
}