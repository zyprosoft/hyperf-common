<?php

namespace ZYProSoft\Aspect;

use Qbhy\HyperfTesting\TestResponse as QbTestResponse;
use PHPUnit\Framework\Assert;

class TestResponse extends QbTestResponse
{
    /**
     * 判定Http状态也判定逻辑状态
     * @return $this|TestResponse
     */
    public function assertOk()
    {
        parent::assertOk();
        Assert::assertJson($this->getContent(), 'content not json!');
        $data = json_decode($this->getContent());
        Assert::assertSame(0, $data->code, 'business code is not success!');
        return $this;
    }
}