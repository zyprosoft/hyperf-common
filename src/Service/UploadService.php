<?php


namespace ZYProSoft\Service;
use Carbon\Carbon;
use Hyperf\HttpMessage\Upload\UploadedFile;
use Overtrue\Flysystem\Qiniu\QiniuAdapter;
use ZYProSoft\Constants\ErrorCode;
use Qiniu\Auth;
use ZYProSoft\Exception\HyperfCommonException;

class UploadService extends AbstractService
{
    public function getQiniuImageUploadToken(string $fileKey)
    {
        $policy = [
            'insertOnly' => true,
            'mimeLimit' => 'image/*',
        ];
        return $this->getQiniuCommonUploadToken($fileKey, $policy);
    }

    public function getQiniuCommonUploadToken(string $fileKey, array $policy = null)
    {
        $accessKey = config('file.qiniu.accessKey');
        $secretKey = config('file.qiniu.secretKey');
        $domain = config('file.qiniu.domain');
        $bucket = config('file.qiniu.bucket');
        if (empty($accessKey) || empty($secretKey) || empty($domain) || empty($bucket)) {
            throw new HyperfCommonException(ErrorCode::SYSTEM_ERROR_QINIU_UPLOAD_CONFIG_NOT_SET);
        }
        $auth = new Auth($accessKey, $secretKey);
        $ttl = config('hyperf-common.upload.qiniu.token_ttl', 3600);
        $token = $auth->uploadToken($bucket, $fileKey, $ttl, $policy);
        return ['token' => $token];
    }

    public function uploadLocalFileToQiniu(UploadedFile $file)
    {
        $stream = fopen($file->getRealPath(), 'r+');
        $fileName = Carbon::now()->getTimestamp().'.'.$file->getExtension();
        $result = $this->fileQiniu()->writeStream($fileName, $stream);
        fclose($stream);
        if (!$result) {
            throw new HyperfCommonException(ErrorCode::SYSTEM_ERROR_UPLOAD_MOVE_FILE_FAIL, "upload move file to qiniu fail!");
        }
        $adapter = $this->fileQiniu();
        if ($adapter instanceof QiniuAdapter) {
            return $adapter->getUrl($fileName);
        }
    }
}