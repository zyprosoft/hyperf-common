<?php


namespace ZYProSoft\Middleware;


use Carbon\Carbon;
use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;
use ZYProSoft\Constants\Constants;
use ZYProSoft\Constants\ErrorCode;
use ZYProSoft\Exception\HyperfCommonException;
use ZYProSoft\Log\Log;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestAuthMiddleware implements MiddlewareInterface
{
    private $appIdSecretList;

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
        $this->appIdSecretList = $this->config->get("hyperf-common.zgw.config_list", []);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        //不是zgw协议不处理签名信息
        if (empty($request->getHeaderLine(Constants::ZYPROSOFT_ZGW))) {
            return $handler->handle($request);
        }

        //如果存在数据签名，需要校验数据签名
        $requestBody = json_decode($request->getBody(), true);
        if (!$requestBody) {
            return $handler->handle($request);
        }
        //如果没有签名校验
        if (!isset($requestBody["auth"])) {
            return $handler->handle($request);
        }
        //签名校验
        $this->checkSign($requestBody);

        return $handler->handle($request);
    }

    private function checkSign($requestBody)
    {
        $auth = $requestBody["auth"];
        $timestamp = $auth["timestamp"];
        $ttl = $this->config->get("hyperf-common.zgw.sign_ttl", 10);
        if (Carbon::now()->timestamp - $timestamp > $ttl) {
            throw new HyperfCommonException(ErrorCode::ZGW_AUTH_SIGNATURE_ERROR, "sign expire!");
        }

        $appId = $auth["appId"];
        //能否找到对应的秘钥
        if (!isset($this->appIdSecretList[$appId])) {
            throw new HyperfCommonException(ErrorCode::ZGW_AUTH_APP_ID_NOT_EXIST);
        }
        $appSecret = $this->appIdSecretList[$appId];

        $param = $requestBody["interface"]["param"];
        $interfaceName = $requestBody["interface"]["name"];
        $param["interfaceName"] = $interfaceName;
        ksort($param);
        $paramString = md5(json_encode($param));

        $nonce = $auth["nonce"];
        $base = "appId=$appId&appSecret=$appSecret&nonce=$nonce&timestamp=$timestamp&$paramString";

        Log::info("sign base:".$base);
        $paramSignature = $auth["signature"];
        $signature = hash_hmac("sha256", $base, $appSecret);
        if ($signature != $paramSignature) {
            Log::error("signature check fail!");

            throw new HyperfCommonException(ErrorCode::ZGW_AUTH_SIGNATURE_ERROR);
        }
        Log::info("check signature($paramSignature) success!");
    }
}