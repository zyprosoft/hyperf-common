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
namespace ZYProSoft;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\HttpServer\CoreMiddleware;
use Psr\SimpleCache\CacheInterface;
use ZYProSoft\Log\StdoutLoggerFactory as ZYProSoftStdLoggerFactory;
use ZYProSoft\Middleware\AppCoreMiddleware as ZYProSoftCoreMiddleware;
use Hyperf\Validation\Middleware\ValidationMiddleware;
use ZYProSoft\Middleware\AppValidationMiddleware as ZYValidationMiddleware;
use Qbhy\HyperfTesting\TestResponse;
use ZYProSoft\Aspect\TestResponse as ZYTestResponse;
use ZYProSoft\Cache\Cache as ZYCache;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                StdoutLoggerInterface::class => ZYProSoftStdLoggerFactory::class,
                CoreMiddleware::class => ZYProSoftCoreMiddleware::class,
                ValidationMiddleware::class => ZYValidationMiddleware::class,
                TestResponse::class => ZYTestResponse::class,
                CacheInterface::class => ZYCache::class,
            ],
            'commands' => [
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                    'ignore_annotations' => [
                        'mixin',
                    ],
                ],
            ],
            // 组件默认配置文件，即执行命令后会把 source 的对应的文件复制为 destination 对应的的文件
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'hyperf-common config.', // 描述
                    // 建议默认配置放在 publish 文件夹中，文件命名和组件名称相同
                    'source' => __DIR__ . '/Publish/hyperf-common.php',  // 对应的配置文件路径
                    'destination' => BASE_PATH . '/config/autoload/hyperf-common.php', // 复制为这个路径下的该文件
                ],
                [
                    'id' => 'shell',
                    'description' => 'hyperf-common server shell.', // 描述
                    // 建议默认配置放在 publish 文件夹中，文件命名和组件名称相同
                    'source' => __DIR__ . '/Publish/service.sh',  // 对应的配置文件路径
                    'destination' => BASE_PATH . '/bin/service.sh', // 复制为这个路径下的该文件
                ],
                [
                    'id' => 'txt',
                    'description' => '敏感词库.', // 描述
                    // 建议默认配置放在 publish 文件夹中，文件命名和组件名称相同
                    'source' => __DIR__ . '/Publish/sensitive.txt',  // 对应的配置文件路径
                    'destination' => BASE_PATH . '/assets/sensitive/sensitive.txt', // 复制为这个路径下的该文件
                ],
            ],
        ];
    }
}
