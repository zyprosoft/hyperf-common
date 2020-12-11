<?php


namespace ZYProSoft\Http;

use ZYProSoft\Constants\Constants;
use ZYProSoft\Log\Log;
use Hyperf\HttpMessage\Cookie\Cookie;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Utils\Context;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Container\ContainerInterface;
use Hyperf\HttpServer\Contract\RequestInterface;

class Response
{
    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @var ResponseInterface
     */
    public $response;

    /**
     * @var RequestInterface
     */
    public $request;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->response = $this->container->get(ResponseInterface::class);
        $this->request = $this->container->get(RequestInterface::class);
    }

    /**
     * 是否ZGW协议
     * @return bool
     */
    public function isZgw()
    {
        return !empty($this->request->getHeaderLine(Constants::ZYPROSOFT_ZGW));
    }

    /**
     * 获取请求参数
     * @return array|mixed
     */
    public function getParams()
    {
        if (!$this->request->isMethod('POST')) {
            return  $this->request->getQueryParams();
        }
        if ($this->isZgw()) {
            return $this->request->post("interface.param");
        }
        return $this->request->post();
    }

    /**
     * 把请求内的信息返回
     * @return array
     */
    public function getResponseParam()
    {
        if (!$this->isZgw()) {
            return [];
        }
        $responseParam = [
            "seqId",
            "eventId",
        ];
        $responseInfo = $this->request->inputs($responseParam);
        $responseInfo["component"] = config("app_name");

        return  $responseInfo;
    }

    public function success($data = []): PsrResponseInterface
    {
        $result = [
            'code' => 0,
            'message' => 'ok',
            'data' => $data,
            'timestamp' => time()
        ];
        $requestInfo = $this->getResponseParam();
        $result = array_merge($requestInfo, $result);

        return $this->response->json($result);
    }

    public function fail($errorCode, $message, $data = []): PsrResponseInterface
    {
        $body = [
            'code' => $errorCode,
            'message' => $message,
            'data' => $data,
            'timestamp' => time()
        ];

        $requestInfo = $this->getResponseParam();
        $result = array_merge($requestInfo, $body);

        $msg = "http request end response fail with content:".json_encode($result);
        Log::req($msg);
        Log::info($msg);

        return $this->response->json($result);
    }

    public function cookie(Cookie $cookie)
    {
        $response = $this->response->withCookie($cookie);
        Context::set(PsrResponseInterface::class, $response);
        return $this;
    }

    public function toWeChatXml(string $xml, int $statusCode = 200): PsrResponseInterface
    {
        $msg = "WeChat http request end response with status($statusCode) content:".$xml;
        Log::req($msg);
        Log::info($msg);

        return $this->response->withStatus($statusCode)
                              ->withAddedHeader("Content-Type", "application/xml; charset=utf-8")
                              ->withBody(new SwooleStream($xml));
    }
}