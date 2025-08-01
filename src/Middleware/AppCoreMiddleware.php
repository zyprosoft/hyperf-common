<?php
/**
 * This file is part of ZYProSoft/Hyperf-Common.
 *
 * @link     https://topicq.icodefuture.com
 * @document https://topicq.icodefuture.com
 * @contact  1003081775@qq.com;微信:zyprosoft
 * @Company  iCodeFuture
 * @license  GPL
 */
declare(strict_types=1);

namespace ZYProSoft\Middleware;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Utils\Arr;
use Hyperf\Utils\Str;
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
use function json_decode;

/**
 * 框架封装的核心文件
 * 在这里解析ZGW协议和上传协议
 * 处理非ZGW协议和微信平台等格式的协议
 * 拥有记录单个请求耗时和内存消耗的统计
 * 可以前置检查请求是否需要进行参数签名
 * Class AppCoreMiddleware
 * @package ZYProSoft\Middleware
 */
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
        $path = $request->getUri()->getPath();
        Log::info("request path:".$path);

        //识别callback请求
        if (Str::startsWith($path, '/callback')) {
            //去掉callback，将后续的三段作为路径
            $path = Str::after($path, '/callback');
            //在头部加入Callback标记
            $request = $request->withAddedHeader(Constants::ZYPROSOFT_CALLBACK, "1");
            return $this->modifyRequestWithPath($request, $path);
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
        Log::info("request body:".$request->getBody());
        Log::info("request parsed body:".json_encode($request->getParsedBody()));

        //增加请求ID
        $remoteAddress = $this->getRemoteAddress($request);
        $remotePort = $this->getRemotePort($request);
        $random = microtime(true)*10000;
        $reqId = "($remoteAddress:$remotePort)-$random";
        $request = $request->withAddedHeader(Constants::ZYPROSOFT_REQ_ID, $reqId);

        //根据会话的token创建sessionID信息
        $sessionName = $this->config->get("session.options.session_name","HYPERF_SESSION_ID");
        $sessionId = null;
        if (strtoupper($request->getMethod()) == 'POST') {
            $token = data_get($request->getParsedBody(), 'token');
            if (isset($token)) {
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
        Log::info("core set session id:$sessionId");
        $request = $request->withCookieParams([$sessionName=>$sessionId]);
        Log::info("modify cookie params result:".json_encode($request->getCookieParams()));

        //识别上传请求
        if ($request->getUri()->getPath() == '/upload') {
            //重新解析获取参数
            $requestBody = $request->getParsedBody();
            if (!isset($requestBody) || empty($requestBody)) {
                Log::info("upload request , but not a zgw protocol request!");
                return  parent::dispatch($request);
            }
            $interfaceValue = data_get($requestBody, 'interface');
            if (!isset($interfaceValue)) {
                Log::info("upload can't find interface param array");
                return parent::dispatch($request);
            }
            $interface = json_decode($interfaceValue, true);
            if ($interface === false) {
                Log::info("upload can't decode interface param as json object");
                return parent::dispatch($request);
            }
            //修改请求body
            data_set($requestBody,'interface', $interface);
            //检查是不是zgw协议
            $interfaceName = Arr::get($interface,'name');
            $param = Arr::get($interface, 'param');
            if (!isset($interfaceName) || !isset($param)) {
                Log::info("upload maybe a zgw request but have error interface content!");
                return  parent::dispatch($request);
            }
            //zgw协议
            $interfaceArray = explode('.', $interfaceName);
            if (count($interfaceArray) != 3) {
                throw new HyperfCommonException(ErrorCode::PARAM_ERROR, "zgw interfaceName is not validate");
            }
            Log::info("check request zgw protocol success, begin dispatch upload request");
            //强制参数校验
            $checkParamExist = ["seqId","eventId","version","timestamp","caller"];
            array_map(function ($paramName) use ($request) {
                $value = data_get($request->getParsedBody(), $paramName);
                if (!isset($value)) {
                    throw new HyperfCommonException(ErrorCode::ZGW_REQUEST_BODY_ERROR,"zgw request body need param $paramName");
                }
            },$checkParamExist);
            //如果开启了强制签名校验
            $forceCheckAuth = $this->config->get("hyperf-common.zgw.force_auth");
            $authValues = data_get($requestBody, 'auth');
            if ($forceCheckAuth && !isset($authValues)) {
                throw  new HyperfCommonException(ErrorCode::ZGW_REQUEST_BODY_ERROR, "zgw force auth need param auth!");
            }
            if(isset($authValues)) {
                $authParams = json_decode($authValues, true);
            }
            if ($forceCheckAuth && !isset($authParams)) {
                Log::error("upload request decode auth param fail!");
                throw  new HyperfCommonException(ErrorCode::ZGW_REQUEST_BODY_ERROR, "zgw force auth need param auth!");
            }

            //如果存在数据签名
            if ($forceCheckAuth) {
                data_set($requestBody,'auth', $authParams);
                $checkAuthParamExist = ["signature","appId","timestamp","nonce"];
                array_map(function ($paramName) use ($authParams) {
                    if (!isset($authParams[$paramName])) {
                        throw new HyperfCommonException(ErrorCode::ZGW_REQUEST_BODY_ERROR,"zgw request body auth need param $paramName");
                    }
                },$checkAuthParamExist);
            }

            $seqId = data_get($requestBody, "seqId");
            $eventId = data_get($requestBody, "eventId");
            $reqId .= "-$seqId-$eventId";
            $request = $request->withHeader(Constants::ZYPROSOFT_REQ_ID, $reqId);
            $request = $request->withHeader(Constants::ZYPROSOFT_UPLOAD, "1");
            //修改请求的body,把是json字符串的解析出来回给request使用
            $request = $request->withParsedBody($requestBody);
            Log::info("upload request after modify parsed body :".json_encode($requestBody));

            //转换成框架的AutoController形式访问接口方法
            //三段表示：大模块名.Controller.Action;大模块通常可以用来标记是哪个大的模块，如管理端可以用Admin
            //需要使用AutoController的"/admin/user/login"这种形式,所以，接口controller必须要设置prefix="/{$interfaceArray[0]}/{$interfaceArray[1]}"
            //才能正常访问到接口方法
            $newPath = "/".$interfaceArray[0]."/".$interfaceArray[1]."/".$interfaceArray[2];
            Log::info("upload request will convert zgw to auto path:$newPath");
            $request = $this->modifyRequestWithPath($request, $newPath);
            $request = $request->withAddedHeader(Constants::ZYPROSOFT_ZGW, "zgw");

            return parent::dispatch($request);
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

            //如果是sse请求不处理
            if (strtolower($contentType) !== "text/event-stream") {
                return parent::dispatch($request);
            }
        }
        
        $requestBody = json_decode($request->getBody()->getContents(), true);
        
        if(!$requestBody) {
            //普通post请求
            Log::info("post method but decode body fail!");
            return parent::dispatch($request);
        }
        
        //是json请求就自动增加content-type:application/json,保证后面可以自动解析body
        if (empty($contentType) || strtolower($contentType) !== "application/json") {
            
            //如果是sse请求不修改
            if (strtolower($contentType) !== "text/event-stream") {
                $request = $request->withoutHeader("content-type");
                $request = $request->withAddedHeader("content-type","application/json");
            }
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
        $request = $request->withHeader(Constants::ZYPROSOFT_REQ_ID, $reqId);

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

    protected function formatBytes(int $bytes)
    {
        if ($bytes > 1024 * 1024) {
            return round($bytes / 1024 / 1024, 2).' MB';
        } elseif ($bytes > 1024) {
            return round($bytes / 1024, 2).' KB';
        }

        return $bytes . ' B';
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        //记录一条开始请求的日志
        $serverParam = $request->getServerParams();
        if (strtoupper($request->getMethod()) == 'POST')
        {
            $params = $request->getBody()->getContents();
        }else{
            $params = json_encode($request->getQueryParams(), JSON_UNESCAPED_UNICODE);
        }
        $uploadTag = $request->getHeaderLine(Constants::ZYPROSOFT_UPLOAD);
        $msg = "";
        if (!empty($uploadTag)) {
            $parsedParams = $request->getParsedBody();
            $msg .= "http request start remote info:".json_encode($serverParam)."  params:".json_encode($parsedParams,JSON_UNESCAPED_UNICODE)." headers:".json_encode($request->getHeaders());
        }else{
            $msg .= "http request start remote info:".json_encode($serverParam)."  params:".$params." headers:".json_encode($request->getHeaders());
        }
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

        //记录输出结果时候的耗时信息
        $msg = "$cost ms || $remoteAddress:$remotePort || headers:$headerInfo || http request end response with content:".$content;

        Log::req($msg);
        Log::info($msg);

        return $response;
    }
}