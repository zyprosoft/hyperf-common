<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace ZYProSoft;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\HttpServer\CoreMiddleware;
use ZYProSoft\Log\StdoutLoggerFactory as ZYProSoftStdLoggerFactory;
use ZYProSoft\Middleware\AppCoreMiddleware as ZYProSoftCoreMiddleware;
use Hyperf\Validation\Middleware\ValidationMiddleware;
use ZYProSoft\Middleware\AppValidationMiddleware as ZYValidationMiddleware;
use Qbhy\HyperfTesting\TestResponse;
use ZYProSoft\Aspect\TestResponse as ZYTestResponse;

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
            ],
        ];
    }
}