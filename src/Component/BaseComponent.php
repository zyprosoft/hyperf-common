<?php
declare(strict_types=1);

namespace ZYProSoft\Component;

use ZYProSoft\Log\Log;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Hyperf\Guzzle\CoroutineHandler;
use Psr\Container\ContainerInterface;
use Swoole\Coroutine;
use Closure;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

class BaseComponent
{
    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @var Client
     */
    protected Client $client;

    /**
     * 初始化client的配置
     * @var array
     */
    protected array $options = [];

    /**
     * 最大重试次数
     * @var int
     */
    protected int $retryCount = 3;

    /**
     * 重试时间
     * @var int
     */
    protected int $retryTime = 1000;

    /**
     * @var MessageFormatter
     */
    protected MessageFormatter $logMsgFormatter;

    /**
     * @var string
     */
    protected string $logMsgTemplate = "{host}||{target}||{req_headers}||{req_body}||{code}||{res_headers}||{res_body}";

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->logMsgFormatter = new MessageFormatter($this->logMsgTemplate);
        $this->client = $this->createClient();
    }

    /**
     * 创建请求client
     * @return Client
     */
    protected function createClient()
    {
        $stack = null;
        if (Coroutine::getCid() > 0) {
            $stack = HandlerStack::create(new CoroutineHandler());
        }

        // 创建重试中间件
        $stack->push(Middleware::retry($this->retryDecider(), $this->retryDelay()));
        // 创建日志请求中间件
        $stack->push(Middleware::log(Log::logger("request"), $this->logMsgFormatter));
        $stack->push(Middleware::log(Log::logger("default"), $this->logMsgFormatter));

        $config = array_replace(['handler' => $stack], $this->options);

        if (method_exists($this->container, 'make')) {
            // Create by DI for AOP.
            return $this->container->make(Client::class, ['config' => $config]);
        }
        return new Client($config);
    }

    /**
     * retryDecider
     * 返回一个匿名函数, 匿名函数若返回false 表示不重试，反之则表示继续重试
     * @return Closure
     */
    protected function retryDecider()
    {
        return function (
            $retries,
            Request $request,
            Response $response = null,
            RequestException $exception = null
        ) {
            // 超过最大重试次数，不再重试
            if ($retries >= $this->retryCount) {
                return false;
            }

            // 请求失败，继续重试
            if ($exception instanceof ConnectException) {
                return true;
            }

            return false;
        };
    }

    /**
     * 返回一个匿名函数，该匿名函数返回下次重试的时间（毫秒）
     * @return Closure
     */
    protected function retryDelay()
    {
        return function ($numberOfRetries) {
            return $this->retryTime;
        };
    }

    public function get(string $uri, $options = [])
    {
        return $this->client->get($uri, $options);
    }

    public function post(string $uri, $options = [])
    {
        return $this->client->post($uri, $options);
    }

    public function success($data = [])
    {
        return ModuleCallResult::success($data);
    }

    public function fail($code, $message = 'fail', $data = [])
    {
        return ModuleCallResult::fail($code, $message, $data);
    }
}