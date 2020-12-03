<?php


namespace ZYProSoft\Middleware;

use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;
use ZYProSoft\Constants\Constants;
use ZYProSoft\Constants\ErrorCode;
use ZYProSoft\Exception\HyperfCommonException;
use ZYProSoft\Facade\Session;
use ZYProSoft\Log\Log;
use Carbon\Carbon;
use Hyperf\HttpServer\CoreMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ZYProSoft\Http\Response;

class AppCoreMiddleware extends CoreMiddleware
{
    /**
     * @var mixed|ConfigInterface
     */
    private $config;

    public function __construct(ContainerInterface $container, string $serverName)
    {
        parent::__construct($container, $serverName);
        $this->config = $this->container->get(ConfigInterface::class);
    }

    //识别特殊请求返回
    public function specialDispatch(ServerRequestInterface $request)
    {
        Log::info("uri:".$request->getUri()->getPath());

        //识别微信请求
        if ($request->getUri()->getPath() == '/weixin') {

            Log::info("request headers:".json_encode($request->getHeaders()));
            if (strtoupper($request->getMethod()) == 'POST') {
                if ($request->getHeaderLine("content-type") == "text/xml" && isset($request->getParsedBody()["MsgType"])) {
                    return $this->modifyRequestWithPath($request, "/weixin/receiveMessage");
                }
            }

            //是不是校验响应的请求
            if (strtoupper($request->getMethod()) == 'GET') {
                $queryParam = $request->getQueryParams();
                if (isset($queryParam["signature"]) && isset($queryParam["echostr"]) && isset($queryParam["timestamp"]) && isset($queryParam["nonce"])) {
                    Log::info("find weixin check response request!");
                    return  $this->modifyRequestWithPath($request, "/weixin/checkResponse");
                }
            }
        }

        return false;
    }

    private function modifyRequestWithPath(ServerRequestInterface $request, string $newPath): ServerRequestInterface
    {
        $oldUri = $request->getUri();
        $oldUri = $oldUri->withPath($newPath);
        $request = $request->withUri($oldUri);
        return  $request;
    }

    private function getRemoteAddress(ServerRequestInterface $request)
    {
        $remoteAddress = '127.0.0.1';
        $xRealIp = $request->getHeaderLine("x-real-ip");
        $xForwardedFor = $request->getHeaderLine("x-forwarded-for");
        $remoteHost = $request->getHeaderLine("remote-host");
        if (!empty($xRealIp)) {
            $remoteAddress = $xRealIp;
        }elseif (!empty($xForwardedFor)) {
            $remoteAddress = $xForwardedFor;
        }elseif (!empty($remoteHost)) {
            $remoteAddress = $remoteHost;
        }else{
            $serverParams = $request->getServerParams();
            if (isset($serverParams["remote_addr"])) {
                $remoteAddress = $serverParams["remote_addr"];
            }
        }

        return $remoteAddress;
    }

    private function getRemotePort(ServerRequestInterface $request)
    {
        $serverParams = $request->getServerParams();
        if (isset($serverParams["remote_port"])) {
            $port = $serverParams["remote_port"];
        }else{
            $port = 12345;
        }
        return $port;
    }

    public function dispatch(ServerRequestInterface $request): ServerRequestInterface
    {
        //打印任意到达的请求
        Log::info("request uri:".$request->getUri()->getPath()." headers:".json_encode($request->getHeaders()));

        //增加请求ID
        $remoteAddress = $this->getRemoteAddress($request);
        $remotePort = $this->getRemotePort($request);
        $random = microtime(true)*10000;
        $reqId = "($remoteAddress:$remotePort)-$random";
        $request = $request->withAddedHeader(Constants::ZYPOSOFT_REQ_ID, $reqId);

        //根据会话的token创建sessionID信息
        $sessionName = $this->config->get("session.options.session_name","HYPERF_SESSION_ID");
        $sessionId = null;
        if (strtoupper($request->getMethod()) == 'POST') {
            $requestBody = json_decode($request->getBody(), true);
            if ($requestBody && isset($requestBody["token"])) {
                $token = $requestBody["token"];
                $sessionId = Session::token2SessionId($token);
            }
        }else{
            $queryParams = $request->getQueryParams();
            if (!empty($queryParams) && isset($queryParams["token"])) {
                $token = $queryParams["token"];
                $sessionId = Session::token2SessionId($token);
            }
        }
        //没有登录使用ip作为会话标志,用来管理同一ip的请求频率
        if (!isset($sessionId)) {
            $sessionId = Session::token2SessionId($remoteAddress);
        }
        if (isset($sessionId)) {
            Log::info("core set session id:$sessionId");
            $request = $request->withCookieParams([$sessionName=>$sessionId]);
            Log::info("modify cookie params result:".json_encode($request->getCookieParams()));
        }

        //处理特殊请求
        $result = $this->specialDispatch($request);
        if ($result instanceof ServerRequestInterface) {
            Log::info("request dispatch to special!");

            return parent::dispatch($result);
        }

        //zgw协议请求篡改,要求全局只能有zgw协议进行请求
        if (strtoupper($request->getMethod()) != 'POST')
        {
            return parent::dispatch($request);
        }
        $contentType = $request->getHeaderLine("content-type");
        //明确不是json请求就不再处理了
        if (!empty($contentType) && strtolower($contentType) !== "application/json") {
            Log::info("request content is not application/json");
            return parent::dispatch($request);
        }
        $requestBody = json_decode($request->getBody(), true);
        if(!$requestBody) {
            //普通post请求
            Log::info("post method but decode body fail!");
            return parent::dispatch($request);
        }
        //是json请求就自动增加content-type:application/json,保证后面可以自动解析body
        if (empty($contentType) || strtolower($contentType) !== "application/json") {
            $request = $request->withoutHeader("content-type");
            $request = $request->withAddedHeader("content-type","application/json");
        }
        $interfaceName = null;
        $param = null;
        if (isset($requestBody["interface"])) {
            if (isset($requestBody["interface"]["name"]) && isset($requestBody["interface"]["param"])) {
                $interfaceName = $requestBody["interface"]["name"];
                $param = $requestBody["interface"]["param"];
            }
        }
        if (!isset($interfaceName) || !isset($param)) {
            //普通post请求
            Log::info("post method but not zgw protocol request!");
            return parent::dispatch($request);
        }
        //zgw协议
        $interfaceArray = explode('.', $interfaceName);
        if (count($interfaceArray) != 3) {
            throw new HyperfCommonException(ErrorCode::PARAM_ERROR, "zgw interfaceName is not validate");
        }
        //强制参数检查
        $checkParamExist = ["seqId","eventId","version","timestamp","caller"];
        foreach ($checkParamExist as $paramName) {
            if (!isset($requestBody[$paramName])) {
                throw new HyperfCommonException(ErrorCode::ZGW_REQUEST_BODY_ERROR,"zgw request body need param $paramName");
            }
        }
        //如果开启了强制签名校验
        $forceCheckAuth = $this->config->get("hyperf-common.zgw.force_auth");
        if ($forceCheckAuth && (!isset($requestBody["auth"]) || empty($requestBody["auth"]))) {
            throw  new HyperfCommonException(ErrorCode::ZGW_REQUEST_BODY_ERROR, "zgw force auth need param auth!");
        }
        //如果存在数据签名
        if (isset($requestBody["auth"])) {
            $checkAuthParamExist = ["signature","appId","timestamp","nonce"];
            $auth = $requestBody["auth"];
            foreach ($checkAuthParamExist as $paramName) {
                if (!isset($auth[$paramName])) {
                    throw new HyperfCommonException(ErrorCode::ZGW_REQUEST_BODY_ERROR,"zgw request body auth need param $paramName");
                }
            }
        }

        $seqId = $requestBody["seqId"];
        $eventId = $requestBody["eventId"];
        $reqId .= "-$seqId-$eventId";
        $request = $request->withHeader(Constants::ZYPOSOFT_REQ_ID, $reqId);

        //转换成框架的AutoController形式访问接口方法
        //三段表示：大模块名.Controller.Action;大模块通常可以用来标记是哪个大的模块，如管理端可以用Admin
        //需要使用AutoController的"/admin/user/login"这种形式,所以，接口controller必须要设置prefix="/{$interfaceArray[0]}/{$interfaceArray[1]}"
        //才能正常访问到接口方法
        $newPath = "/".$interfaceArray[0]."/".$interfaceArray[1]."/".$interfaceArray[2];
        Log::info("will convert zgw to auto path:$newPath");
        $request = $this->modifyRequestWithPath($request, $newPath);
        $request = $request->withAddedHeader(Constants::ZYPROSOFT_ZGW, "zgw");

        return parent::dispatch($request);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        //记录一条开始请求的日志
        $serverParam = $request->getServerParams();
        if (strtoupper($request->getMethod()) == 'POST')
        {
            $params = $request->getBody()->getContents();
        }else{
            $params = json_encode($request->getQueryParams());
        }
        $msg = "http request start remote info:".json_encode($serverParam)."  params:".$params." headers:".json_encode($request->getHeaders());
        Log::req($msg);
        Log::info($msg);

        $startTime = Carbon::now();
        $response = parent::process($request, $handler);
        $cost = Carbon::now()->diffInRealMilliseconds($startTime);

        //响应成功记录
        $content = $response->getBody()->getContents();
        $remoteAddress = $this->getRemoteAddress($request);
        $remotePort = $this->getRemotePort($request);
        $headerInfo = json_encode($response->getHeaders());
        $msg = "$cost ms || $remoteAddress:$remotePort || headers:$headerInfo || http request end response with content:".$content;
        Log::req($msg);
        Log::info($msg);

        return $response;
    }
}