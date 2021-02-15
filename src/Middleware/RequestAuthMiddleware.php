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

namespace ZYProSoft\Middleware;


use Carbon\Carbon;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Utils\Arr;
use Psr\Container\ContainerInterface;
use ZYProSoft\Constants\Constants;
use ZYProSoft\Constants\ErrorCode;
use ZYProSoft\Exception\HyperfCommonException;
use ZYProSoft\Log\Log;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 请求签名鉴权插件
 * Class RequestAuthMiddleware
 * @package ZYProSoft\Middleware
 */
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

    private function checkSign(array $requestBody)
    {
        $auth = $requestBody["auth"];
        $timestamp = $auth["timestamp"];
        $ttl = $this->config->get("hyperf-common.zgw.sign_ttl", 10);
        $secondDidPass = Carbon::now()->diffInRealSeconds(Carbon::createFromTimestamp($timestamp));
        Log::info("sign time did pass $secondDidPass seconds!");
        if ($secondDidPass > $ttl) {
            Log::info("sign has expired!");
            throw new HyperfCommonException(ErrorCode::ZGW_AUTH_SIGNATURE_ERROR, "sign expire!");
        }

        $appId = $auth["appId"];
        //能否找到对应的秘钥
        if (!isset($this->appIdSecretList[$appId])) {
            throw new HyperfCommonException(ErrorCode::ZGW_AUTH_APP_ID_NOT_EXIST);
        }
        $appSecret = $this->appIdSecretList[$appId];

        $param = Arr::get($requestBody, 'interface.param');
        $interfaceName = Arr::get($requestBody, 'interface.name');
        $param["interfaceName"] = $interfaceName;
        ksort($param);
        $paramJson = json_encode($param, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        Log::info("param json:$paramJson");
        $paramString = md5($paramJson);

        $nonce = $auth["nonce"];
        $base = "appId=$appId&appSecret=$appSecret&nonce=$nonce&timestamp=$timestamp&$paramString";

        Log::info("sign base:".$base);
        $paramSignature = $auth["signature"];
        $signature = hash_hmac("sha256", $base, $appSecret);
        if ($signature != $paramSignature) {
            Log::error("signature check fail!");
            Log::info("server sign:$signature");
            Log::info("client sign:$paramSignature");

            throw new HyperfCommonException(ErrorCode::ZGW_AUTH_SIGNATURE_ERROR);
        }
        Log::info("check signature($paramSignature) success!");
    }
}