<?php


namespace ZYProSoft\Event;


class UserDidGetUploadToken
{
    protected int $userId;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }
}