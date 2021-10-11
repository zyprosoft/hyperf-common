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

namespace ZYProSoft\Middleware;


use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\SessionInterface;
use Hyperf\Utils\Str;
use Psr\Container\ContainerInterface;
use ZYProSoft\Constants\ErrorCode;
use ZYProSoft\Exception\HyperfCommonException;
use ZYProSoft\Log\Log;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * 请求频率限制插件
 * Class RequestLimitMiddleware
 * @package ZYProSoft\Middleware
 */
class RequestLimitMiddleware implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->config = $this->container->get(ConfigInterface::class);
        $this->cache = $this->container->get(CacheInterface::class);
        $this->session = $this->container->get(SessionInterface::class);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $enable = $this->config->get('hyperf-common.rate_limit.enable');
        if (!$enable) {
            return $handler->handle($request);
        }

        //接口地址
        $uri = $request->getUri()->getPath();
        //白名单限制
        $whiteList = $this->config->get("hyperf-common.rate_limit.white_list", []);
        $isMatched = false;
        foreach ($whiteList as $uriItem)
        {
            Log::info("uriItem:$uriItem uri:$uri");
            if ($uriItem === $uri) {
                $isMatched = true;
                break;
            }
            if (Str::endsWith($uriItem, '*')) {
                if(Str::is($uriItem, $uri)) {
                    $isMatched = true;
                    Log::info("matched limit white list end by * character! :$uriItem");
                    break;
                }
            }
        }
        if ($isMatched) {
            return  $handler->handle($request);
        }

        $uri = str_replace("/", "_", $uri);
        $sessionId = $this->session->getId();
        $cachePrefix = "req_lm";
        $cacheKey = $cachePrefix."_".$sessionId."_".$uri;
        $ttl = $this->config->get('hyperf-common.rate_limit.access_rate_ttl', 20);
        $count = $this->cache->get($cacheKey, 0);
        $limitCount = $this->config->get("hyperf-common.rate_limit.access_rate_limit", 10);
        if ($count > $limitCount) {
            Log::error("request reach rate limit $cacheKey count:$count in ttl:$ttl");

            throw new HyperfCommonException(ErrorCode::REQUEST_RATE_LIMIT);
        }
        $count += 1;
        $this->cache->set($cacheKey, $count, $ttl);
        Log::info("$cacheKey increase request count:".$count);

        return $handler->handle($request);
    }
}