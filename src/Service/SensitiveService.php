<?php


namespace ZYProSoft\Service;

use DfaFilter\SensitiveHelper;
use Hyperf\Di\Annotation\Inject;
use ZYProSoft\Log\Log;

class SensitiveService
{
    const SENSITIVE_BASE_PATH = __DIR__.'/SensitiveAssets/';

    private array $sensitivePaths = [
        'ad.txt',
        'part1.txt',
        'po.txt',
    ];

    /**
     * @Inject
     *  敏感词捕捉器
     * @var SensitiveHelper|object|null
     */
    private SensitiveHelper $handle;

    public function __construct()
    {
        $this->handle = new SensitiveHelper();
        foreach ($this->sensitivePaths as $path) {
            $realPath = self::SENSITIVE_BASE_PATH.$path;
            Log::info("sensitive config path:$realPath");
            $this->handle->setTreeByFile($realPath);
        }
    }

    public function isLegal($content)
    {
        $result =  $this->handle->islegal($content);
        if($result == false) {
            return false;
        }
        Log::error("($content)发现敏感词内容");
        return $result;
    }
}