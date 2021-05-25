<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace ZYProSoft\ElasticSearch;

use Elasticsearch\ConnectionPool\StaticConnectionPool;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ContainerInterface;
use Hyperf\Elasticsearch\ClientBuilderFactory;
use Hyperf\Scout\Engine\ElasticsearchEngine;
use Hyperf\Scout\Engine\Engine;
use Hyperf\Scout\Provider\ProviderInterface;

class ElasticsearchProvider implements ProviderInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function make(string $name): Engine
    {
        $config = $this->container->get(ConfigInterface::class);
        //采用腾讯云的搜索ES服务器，不需要去嗅探节点
        //https://cloud.tencent.com/document/product/845/19538
        $builder = $this->container->get(ClientBuilderFactory::class)->create()->setConnectionPool(StaticConnectionPool::class);
        $client = $builder->setHosts($config->get("scout.engine.{$name}.hosts"))->build();
        $index = $config->get("scout.engine.{$name}.index");
        return new ElasticsearchEngine($client, $index);
    }
}