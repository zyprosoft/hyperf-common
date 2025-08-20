<?php
/**
 * This file is part of Motong-Admin.
 *
 * @link     https://github.com/MotongAdmin
 * @document https://github.com/MotongAdmin
 * @contact  1003081775@qq.com
 * @author   zyvincent 
 * @Company  Icodefuture Information Technology Co., Ltd.
 * @license  GPL
 */
declare(strict_types=1);

namespace ZYProSoft\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ZYProSoft\Util\CamelCaseUtil;
use Hyperf\HttpMessage\Stream\SwooleStream;

/**
 * 驼峰命名转换中间件
 * 自动将响应数据中的键名转换为驼峰命名
 */
class CamelCaseMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // 从请求头里面获取是否需要转换结果为驼峰格式，有的客户端要求必须要转换，有的客户端要求不转换
        $responseCamelCase = $request->getHeaderLine('response-camel-case');
        if (!isset($responseCamelCase) || $responseCamelCase != 'true') {
            return $handler->handle($request);
        }

        $response = $handler->handle($request);
        
        // 只处理成功的JSON响应
        if ($response->getStatusCode() !== 200) {
            return $response;
        }

        $contentType = $response->getHeaderLine('Content-Type');
        if (strpos($contentType, 'application/json') === false) {
            return $response;
        }

        return $this->processResponse($response);
    }

    /**
     * 处理响应数据
     */
    private function processResponse(ResponseInterface $response): ResponseInterface
    {
        // 获取响应内容
        $body = $response->getBody();
        $contents = $body->getContents();
        
        // 如果响应体为空，直接返回
        if (empty($contents)) {
            return $response;
        }
        
        // 解析JSON响应
        $responseData = json_decode($contents, true);
        
        // 如果解析失败或不是数组，直接返回原响应
        if (!is_array($responseData)) {
            return $response;
        }
        
        // 检查是否有data字段并且非空，否则就原样返回
        if (!isset($responseData['data']) || empty($responseData['data'])) {
            return $response;
        }
        
        // 转换data字段为驼峰命名
        $bizData = $responseData['data'];
        if (is_array($bizData)) {
            $responseData['data'] = CamelCaseUtil::quick($bizData);
        }
        
        // 创建新的响应流
        $newBody = new SwooleStream(json_encode($responseData, JSON_UNESCAPED_UNICODE));
        
        // 创建新的响应对象，保持原有的状态码和头部信息
        $newResponse = $response->withBody($newBody);
        
        return $newResponse;
    }
}