<?php


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
 * @AutoController(prefix="/common/upload")
 * Class UploadController
 * @package App\Controller\Common
 */
class UploadController extends AbstractController
{
    /**
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
            throw new HyperfCommonException(ErrorCode::SYSTEM_ERROR_UPLOAD_FILE_SIZE_TOO_BIG);
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
                $type = Str::before($limitType, '*');
                $type = str_replace('/', '\/', $type);
                $pattern = '/^'.$type.'\w+$/';
                Log::info("check mimetype use pattern:".$pattern);
                if (Str::is($pattern, $mimeType)) {
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
            throw new HyperfCommonException(ErrorCode::SYSTEM_ERROR_UPLOAD_FILE_MIME_NOT_ALLOWED);
        }

        $systemType = config('hyperf-common.upload.system_type');
        if ($systemType == Constants::UPLOAD_SYSTEM_TYPE_LOCAL) {
            if (Str::is('/^image\/\w+$/', $mimeType)) {
                $localDir = config('hyperf-common.upload.local.image_dir');
            }else{
                $localDir = config('hyperf-common.upload.local.common_dir');
            }
            $localPublicUrl = config('hyperf-common.upload.local.url_prefix');

            $fileRename = Carbon::now()->getTimestamp().'.'.$file->getExtension();
            $result = $this->moveFileToPublic('upload', $localDir, $fileRename);
            if (!$result) {
                throw new HyperfCommonException(ErrorCode::SYSTEM_ERROR_UPLOAD_MOVE_FILE_FAIL);
            }
            $publicImageUrl = $localPublicUrl.$localDir.DIRECTORY_SEPARATOR.$fileRename;
            return  $this->success([
                'url' => $publicImageUrl
            ]);
        }
        //不传本地就传七牛云，其他的后面再说吧
        $result = $this->service->uploadLocalFileToQiniu($file->getRealPath());
        return $this->success($result);
    }

    public function getUploadImageToken(AuthedRequest $request)
    {
        $this->validate([
            'fileKey' => 'string|required|min:1'
        ]);
        $fileKey = $request->param('fileKey');
        $result = $this->service->getQiniuImageUploadToken($fileKey);
        return $this->success($result);
    }

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