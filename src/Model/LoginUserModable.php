<?php

namespace ZYProSoft\Model;

use Qbhy\HyperfAuth\Authenticatable;

interface LoginUserModable extends Authenticatable
{
    public function getId();

    public static function retrieveById($key): ?LoginUserModable;

    public function isAdmin();

    public static function getByToken($token): ?LoginUserModable;
}