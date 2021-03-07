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

namespace ZYProSoft\Facade;

use App\Model\User;
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
        if ($user instanceof User) {
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

    public static function login(Authenticatable $user)
    {
        return self::authManager()->login($user);
    }

    public static function logout()
    {
        return self::authManager()->logout();
    }
}