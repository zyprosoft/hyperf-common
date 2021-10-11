<?php
/**
 * This file is part of ZYProSoft/Hyperf-Common.
 *
 * @link     https://topicq.icodefuture.com
 * @document https://topicq.icodefuture.com
 * @contact  1003081775@qq.com;微信:zyprosoft
 * @Company  吉安码动未来信息科技有限公司
 * @license  GPL
 */
declare(strict_types=1);
namespace ZYProSoft\Facade;


use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\AsyncQueue\JobInterface;
use Hyperf\Utils\ApplicationContext;

/**
 * 队列的Facade
 * Class Queue
 * @package ZYProSoft\Facade
 */
class Queue
{
    public static function queue(string $name)
    {
        return ApplicationContext::getContainer()->get(DriverFactory::class)->get($name);
    }

    public static function defaultQueue()
    {
        return self::queue("default");
    }

    public static function push(JobInterface $job, int $delay = 0)
    {
        self::defaultQueue()->push($job, $delay);
    }
}