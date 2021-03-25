<?php


namespace ZYProSoft\Service;

use DfaFilter\SensitiveHelper;
use Hyperf\Di\Annotation\Inject;
use ZYProSoft\Log\Log;

class SensitiveService
{
    const SENSITIVE_BASE_PATH = BASE_PATH . '/assets/sensitive/';

    /**
     * 默认的
     * @var array|string[]
     */
    private array $defaultPaths = [
        'sensitive.txt',
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
        foreach ($this->defaultPaths as $path) {
            $realPath = self::SENSITIVE_BASE_PATH.$path;
            Log::info("sensitive default config path:$realPath");
            $this->handle->setTreeByFile($realPath);
        }

    }

    /**
     * 外部增加自定义的敏感词库
     * @param array $paths
     * @throws \DfaFilter\Exceptions\PdsBusinessException
     */
    public function addSensitivePaths(array $paths)
    {
        foreach ($paths as $path) {
            Log::info("sensitive add custom config path:$path");
            $this->handle->setTreeByFile($path);
        }
    }

    public function isSensitive($content)
    {
        $result =  $this->handle->getBadWord($content,1);
        if(empty($result)) {
            return false;
        }
        Log::error("($content)发现敏感词内容:".implode(';',$result));
        return true;
    }
}