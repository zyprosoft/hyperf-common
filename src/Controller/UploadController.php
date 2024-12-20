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

namespace ZYProSoft\Controller;
use Hyperf\Utils\Str;
use ZYProSoft\Constants\Constants;
use Carbon\Carbon;
use ZYProSoft\Constants\ErrorCode;
use ZYProSoft\Exception\HyperfCommonException;
use ZYProSoft\Http\AuthedRequest;
use ZYProSoft\Log\Log;
use ZYProSoft\Service\UploadService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;

/**
 * 如果你需要上传文件然后还有特殊业务逻辑
 * 可以采用Aspect方式注入uploadFile方法，
 * 在上传前校验你需要的参数，在上传完成后，将文件路径记录到数据库
 * 这些都是你可以基于这个基础的上传能力来完善的
 * 或者这个上传的处理方式可以作为你的一个参照，用于实现别的上传能力的接口
 * @AutoController(prefix="/common/upload")
 * Class UploadController
 * @package App\Controller\Common
 */
class UploadController extends AbstractController
{
    /**
     * 上传的逻辑服务
     * @Inject
     * @var UploadService
     */
    protected UploadService $service;

    /**
     * 必须是授权身份
     * @param AuthedRequest $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function uploadFile(AuthedRequest $request)
    {
        if (!$this->hasFile('upload')) {
            throw new HyperfCommonException(ErrorCode::SYSTEM_ERROR_NO_UPLOAD_FILE_FOUND,"no upload file use key \<upload\> been found");
        }
        $file = $request->file('upload');

        //校验文件大小
        $maxFileSize = config('hyperf-common.upload.max_file_size');
        $size = $file->getSize();
        if ($size > $maxFileSize) {
            Log::info("upload file size:$size is over max file size:$maxFileSize");
            throw new HyperfCommonException(ErrorCode::SYSTEM_ERROR_UPLOAD_FILE_SIZE_TOO_BIG,"upload file is too big!");
        }

        //校验文件类型
        $fileTypeLimit = explode(';',config('hyperf-common.upload.file_type_limit'));
        $mimeType = Str::lower($file->getMimeType());
        Log::info("upload file mimeType is:".$mimeType);
        $isMimeValidate = false;
        foreach ($fileTypeLimit as $limitType)
        {
            if ($limitType == '*') {
                $isMimeValidate = true;
                break;
            }
            if (Str::endsWith($limitType, '*')) {
                Log::info("check mimetype use pattern:".$limitType);
                if (Str::is($limitType, $mimeType)) {
                    $isMimeValidate = true;
                }
            }else{
                if (Str::lower($limitType) == $mimeType) {
                    $isMimeValidate = true;
                    break;
                }
            }
        }
        if (!$isMimeValidate) {
            Log::info("allowed file mimetype is:".json_encode($fileTypeLimit));
            throw new HyperfCommonException(ErrorCode::SYSTEM_ERROR_UPLOAD_FILE_MIME_NOT_ALLOWED,"upload file type is not allowed");
        }

        $systemType = config('hyperf-common.upload.system_type');
        if ($systemType == Constants::UPLOAD_SYSTEM_TYPE_LOCAL) {
            if (Str::is('image/*', $mimeType)) {
                $localDir = config('hyperf-common.upload.local.image_dir');
            }else{
                $localDir = config('hyperf-common.upload.local.common_dir');
            }
            $localPublicUrl = config('hyperf-common.upload.local.url_prefix');

            $fileRename = Carbon::now()->getTimestampMs().'.'.$file->getExtension();
            $result = $this->moveFileToPublic('upload', $localDir, $fileRename);
            if (!$result) {
                throw new HyperfCommonException(ErrorCode::SYSTEM_ERROR_UPLOAD_MOVE_FILE_FAIL,"upload file move fail!");
            }
            $publicImageUrl = $localPublicUrl.$localDir.DIRECTORY_SEPARATOR.$fileRename;
            return  $this->success([
                'url' => $publicImageUrl
            ]);
        }
        //不传本地就传七牛云，其他的太贵了不考虑了
        $result = $this->service->uploadLocalFileToQiniu($file);
        Log::info("success upload to qiniu:$result");
        return $this->success(['url'=>$result]);
    }

    /**
     * 获取七牛对象存储的图片上传Token
     * 获取的Token只能用于上传图片类型的文件
     * @param AuthedRequest $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getUploadImageToken(AuthedRequest $request)
    {
        $this->validate([
            'fileKey' => 'string|required|min:1'
        ]);
        $fileKey = $request->param('fileKey');
        $result = $this->service->getQiniuImageUploadToken($fileKey);
        return $this->success($result);
    }

    /**
     * 获取七牛对象存储的通用文件的上传Token
     * @param AuthedRequest $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getUploadToken(AuthedRequest $request)
    {
        $this->validate([
            'fileKey' => 'string|required|min:1'
        ]);
        $fileKey = $request->param('fileKey');
        $result = $this->service->getQiniuCommonUploadToken($fileKey);
        return $this->success($result);
    }
}