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

namespace ZYProSoft\Facade;

use ZYProSoft\Model\LoginUserModable;
use Hyperf\Utils\ApplicationContext;
use Qbhy\HyperfAuth\Authenticatable;
use Qbhy\HyperfAuth\AuthManager;

/**
 * Token鉴权相关的Facade
 * Class Auth
 * @package ZYProSoft\Facade
 */
class Auth
{
    public static function authManager()
    {
        return ApplicationContext::getContainer()->get(AuthManager::class);
    }

    public static function user()
    {
        return self::authManager()->user();
    }

    public static function isAdmin()
    {
        $user = self::user();
        if ($user instanceof LoginUserModable) {
            return $user->isAdmin();
        }
        return  false;
    }

    public static function userId()
    {
        return self::user()->getId();
    }

    public static function isLogin()
    {
        return self::authManager()->check();
    }

    public static function isGuest()
    {
        return self::authManager()->guest();
    }

    public static function login(LoginUserModable $user)
    {
        return self::authManager()->login($user);
    }

    public static function logout()
    {
        return self::authManager()->logout();
    }

    /**
     * 分钟要转成秒
     */
    public static function tokenTTL()
    {
        $guard = config('auth.default.guard');
        return config('auth.guards.'.$guard.'.ttl')*60;
    }

    /**
     * 单位是秒
     */
    public static function refreshTokenTTL()
    {
        $guard = config('auth.default.guard');
        return config('auth.guards.'.$guard.'.refresh_ttl');
    }
}