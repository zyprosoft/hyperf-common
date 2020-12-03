<?php


namespace ZYProSoft\Facade;

use Hyperf\Utils\ApplicationContext;
use Qbhy\HyperfAuth\Authenticatable;
use Qbhy\HyperfAuth\AuthManager;

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

    public static function login(Authenticatable $user)
    {
        return self::authManager()->login($user);
    }

    public static function logout()
    {
        return self::authManager()->logout();
    }
}