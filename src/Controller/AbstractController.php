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

namespace ZYProSoft\Controller;

use Hyperf\Utils\Str;
use ZYProSoft\Http\Request;
use Hyperf\Contract\ContainerInterface;
use ZYProSoft\Http\Response;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use ZYProSoft\Constants\ErrorCode;
use ZYProSoft\Exception\HyperfCommonException;
use ZYProSoft\Service\PublicFileService;
use ZYProSoft\Service\CaptchaService;

/**
 * 控制器基类，集成请求的验证
 * 请求类型的识别等基础功能
 * 文件上传请求的相关处理
 * Class AbstractController
 * @package ZYProSoft\Controller
 */
abstract class AbstractController
{
    /**
     * @var ContainerInterface
     */
    protected ContainerInterface $container;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var ValidatorFactoryInterface
     */
    protected $validatorFactory;

    /**
     * @var PublicFileService
     */
    protected $publicFileService;

    /**
     * @var CaptchaService;
     */
    protected CaptchaService $captchaService;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->request = $container->get(Request::class);
        $this->response = $container->get(Response::class);
        $this->validatorFactory = $container->get(ValidatorFactoryInterface::class);
        $this->publicFileService = $container->get(PublicFileService::class);
        $this->captchaService = $container->get(CaptchaService::class);
    }

    /**
     * 需要自定义错误消息可以重载
     * @return array
     */
    public function messages()
    {
        return [
            'sensitive' => '内容包含敏感词汇!',
        ];
    }

    /**
     * 指定规则检测
     * @param $rules
     * @return array
     */
    public function validate($rules)
    {
        $validator = $this->validatorFactory->make($this->request->getParams(), $rules, $this->messages());
        $validator->validate();
        if ($validator->fails()) {
            $errorMsg = $validator->errors()->first();
            throw new HyperfCommonException(ErrorCode::PARAM_ERROR, $errorMsg);
        }
        return $validator->validated();
    }

    /**
     * 快捷验证指定参数数组
     * @param $rules
     * @param array $params
     * @param array $messages
     */
    public function validateParams($rules, array $params, array $messages = [])
    {
        $messages = $this->messages() + $messages;
        $validator = $this->validatorFactory->make($params, $rules, $messages);
        $validator->validate();
        if ($validator->fails()) {
            $errorMsg = $validator->errors()->first();
            throw new HyperfCommonException(ErrorCode::PARAM_ERROR, $errorMsg);
        }
        return $validator->validated();
    }

    /**
     * 通用的验证码校验逻辑,如果该接口需要验证验证码，应该固定在参数里面传递
     *  'captcha' => [
     *      'key' => 'xxxx',
     *      'code' => 'xxx'
     *  ]
     */
    protected function validateCaptcha()
    {
        $this->validate([
            'captcha.key' => 'string|required|min:1',
            'captcha.code' => 'string|required|min:1',
        ]);
        //先校验验证码是否正确
        $key = $this->request->param('captcha.key');
        $code = $this->request->param('captcha.code');
        $this->captchaService->validate($key, $code);
    }

    /**
     * 获取当前请求用户的ID
     * 这个是通过Auth的token反查回来的
     * @return mixed
     */
    protected function getUserId()
    {
        return $this->request->getUserId();
    }

    /**
     * 返回成功响应
     * @param array $result
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function success($result = [])
    {
       return $this->response->success($result);
    }

    /**
     * 返回微信格式的成功响应
     * @param string $result
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function weChatSuccess($result = '')
    {
        return $this->response->toWeChatXml($result);
    }

    /**
     * 获取请求中的文件信息
     * @param string $fileName
     * @return \Hyperf\HttpMessage\Upload\UploadedFile|\Hyperf\HttpMessage\Upload\UploadedFile[]|null
     */
    protected function file(string $fileName)
    {
        return $this->request->file($fileName);
    }

    /**
     * 请求中是否包含指定的文件信息
     * @param string $fileName
     * @return bool
     */
    protected function hasFile(string $fileName)
    {
        return $this->request->hasFile($fileName);
    }

    /**
     * 请求中的文件是否合法
     * @param string $fileName
     * @return bool
     */
    protected function isFileValid(string $fileName)
    {
        return $this->file($fileName)->isValid();
    }

    /**
     * 上传文件的临时路径
     * @param string $fileName
     * @return string
     */
    protected function fileTmpPath(string $fileName)
    {
        return $this->file($fileName)->getPath();
    }

    /**
     * 上传文件的扩展名
     * @param string $fileName
     * @return string|null
     */
    protected function fileExtension(string $fileName)
    {
        return $this->file($fileName)->getExtension();
    }

    /**
     * 将上传文件从临时目录移动到指定目录完成上传
     * @param string $fileName
     * @param string $destination
     * @return bool
     */
    protected function moveFile(string $fileName, string $destination)
    {
        $file = $this->file($fileName);
        $file->moveTo($destination);
        $isMoved = $file->isMoved();
        if (!$isMoved) {
            return false;
        }
        return chmod($destination,0744);
    }

    /**
     * 获取服务的公开可访问目录路径
     * @return mixed
     */
    protected function publicRootPath()
    {
        return $this->publicFileService->publicRootPath();
    }

    /**
     * 如果公开目录不存在则创建出来
     * @return bool
     */
    protected function createPublicDirIfNotExist()
    {
        return $this->publicFileService->createPublicDirIfNotExist();
    }

    /**
     * 在公开目录下面创建一个子目录
     * @param string $subDir
     * @return bool
     */
    protected function createPublicSubDirIfNotExist(string $subDir)
    {
        return $this->publicFileService->createPublicSubDirIfNotExist($subDir);
    }

    /**
     * 获取一个基于公开目录的子目录路径
     * @param string $subPath
     * @return string|null
     */
    protected function publicPath(string $subPath)
    {
        return $this->publicFileService->publicPath($subPath);
    }

    /**
     * 删除公开目录下的一个子目录
     * @param string $subPath
     * @return bool
     */
    protected function deletePublicPath(string $subPath)
    {
        return $this->publicFileService->deletePublicPath($subPath);
    }

    /**
     * 把指定文件移动到公开目录下指定的子目录
     * @param string $fileName
     * @param string|null $subDir
     * @param string|null $fileRename
     * @param bool $autoCreateDir
     * @return bool
     */
    protected function moveFileToPublic(string $fileName, string $subDir = null, string $fileRename = null,  $autoCreateDir = true)
    {
        if (!isset($fileRename)) {
            $fileRename = Str::random(6);
        }
        if (!isset($subDir)) {
            if ($autoCreateDir) {
                $result = $this->createPublicDirIfNotExist();
                if (!$result) {
                    return false;
                }
            }
            $destination = $this->publicRootPath().DIRECTORY_SEPARATOR.$fileRename;
        }else{
            if ($autoCreateDir) {
                $result = $this->createPublicSubDirIfNotExist($subDir);
                if (!$result) {
                    return false;
                }
            }
            $destination = $this->publicPath($subDir).DIRECTORY_SEPARATOR.$fileRename;
        }
        return $this->moveFile($fileName, $destination);
    }
}