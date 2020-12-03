<?php


namespace ZYProSoft\Facade;


use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\AsyncQueue\JobInterface;
use Hyperf\Utils\ApplicationContext;

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