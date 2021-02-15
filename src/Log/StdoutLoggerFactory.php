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

namespace ZYProSoft\Log;

use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerInterface;

class StdoutLoggerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return ApplicationContext::getContainer()->get(LoggerFactory::class)->get('framework', 'system');
    }
}