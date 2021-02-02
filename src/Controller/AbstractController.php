<?php
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
     * 指定规则检测
     * @param $rules
     * @return array
     */
    public function validate($rules)
    {
        $validator = $this->validatorFactory->make($this->request->getParams(), $rules);
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

    protected function getUserId()
    {
        return $this->request->getUserId();
    }

    protected function success($result = [])
    {
       return $this->response->success($result);
    }

    protected function weChatSuccess($result = '')
    {
        return $this->response->toWeChatXml($result);
    }

    protected function file(string $fileName)
    {
        return $this->request->file($fileName);
    }

    protected function hasFile(string $fileName)
    {
        return $this->request->hasFile($fileName);
    }

    protected function isFileValid(string $fileName)
    {
        return $this->file($fileName)->isValid();
    }

    protected function fileTmpPath(string $fileName)
    {
        return $this->file($fileName)->getPath();
    }

    protected function fileExtension(string $fileName)
    {
        return $this->file($fileName)->getExtension();
    }

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

    protected function publicRootPath()
    {
        return $this->publicFileService->publicRootPath();
    }

    protected function createPublicDirIfNotExist()
    {
        return $this->publicFileService->createPublicDirIfNotExist();
    }

    protected function createPublicSubDirIfNotExist(string $subDir)
    {
        return $this->publicFileService->createPublicSubDirIfNotExist($subDir);
    }

    protected function publicPath(string $subPath)
    {
        return $this->publicFileService->publicPath($subPath);
    }

    protected function deletePublicPath(string $subPath)
    {
        return $this->publicFileService->deletePublicPath($subPath);
    }

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