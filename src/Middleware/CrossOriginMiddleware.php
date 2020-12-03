<?php


namespace ZYProSoft\Middleware;


use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpMessage\Exception\HttpException;
use Hyperf\Utils\Context;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CrossOriginMiddleware implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @var ConfigInterface
     */
    private $config;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->config = $this->container->get(ConfigInterface::class);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        //是不是跨域预判定请求
        if (! $this->isCorsRequest($request)) {
            return $handler->handle($request);
        }

        //读取配置
        $enableCrossOrigin = $this->config->get('hyperf-common.cors.enable_cross_origin',false);
        if (!$enableCrossOrigin) {
            //不准跨域
            throw new HttpException(403, "not allowed cross origin");
        }

        //读取允许跨域域名
        $origin = $request->getHeaderLine("Origin");
        $configOrigins = $this->config->get("hyperf-common.cors.allow_cross_origins");
        if (!in_array($origin, $configOrigins)) {
            //不准跨域
            throw new HttpException(403, "not allowed cross origin");
        }

        //允许跨域
        $response = Context::get(ResponseInterface::class);
        $response = $response->withAddedHeader("Access-Control-Allow-Origin", $origin);
        $response = $response->withAddedHeader("Access-Control-Allow-Credentials", "true");
        $response = $response->withAddedHeader('Access-Control-Allow-Headers', 'DNT,Keep-Alive,User-Agent,Cache-Control,Content-Type,hyperf-session-id');
        Context::set(ResponseInterface::class, $response);

        return  $response;
    }

    private function isCorsRequest(ServerRequestInterface $request)
    {
        return $request->hasHeader("Origin") && !$this->isSameHost($request);
    }

    private function isSameHost(ServerRequestInterface $request)
    {
        return $request->getHeaderLine("Origin") === $request->getUri()->getScheme()."://".$request->getUri()->getHost();
    }
}