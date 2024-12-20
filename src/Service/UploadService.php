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

namespace ZYProSoft\Service;
use Carbon\Carbon;
use Hyperf\HttpMessage\Upload\UploadedFile;
use Overtrue\Flysystem\Qiniu\QiniuAdapter;
use ZYProSoft\Constants\ErrorCode;
use Qiniu\Auth;
use ZYProSoft\Event\UserDidGetUploadToken;
use ZYProSoft\Exception\HyperfCommonException;

/**
 * 文件上传服务的提供
 * 主要是基于七牛存储和本地存储的实现
 * Class UploadService
 * @package ZYProSoft\Service
 */
class UploadService extends AbstractService
{
    public function getQiniuImageUploadToken(string $fileKey)
    {
        $policy = [
            'insertOnly' => 1,
            'mimeLimit' => 'image/*',
        ];
        return $this->getQiniuCommonUploadToken($fileKey, $policy);
    }

    public function getQiniuCommonUploadToken(string $fileKey, array $policy = null)
    {
        $accessKey = config('file.storage.qiniu.accessKey');
        $secretKey = config('file.storage.qiniu.secretKey');
        $bucket = config('file.storage.qiniu.bucket');
        if (empty($accessKey) || empty($secretKey) || empty($bucket)) {
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
        $dirname = config('file.storage.qiniu.app_dir')."/";
        $now = Carbon::now()->getTimestamp();
        $fileName = $dirname.$now.'.'.$file->getExtension();
        $result = $this->fileQiniu()->writeStream($fileName, $stream);
        fclose($stream);
        if (!$result) {
            throw new HyperfCommonException(ErrorCode::SYSTEM_ERROR_UPLOAD_MOVE_FILE_FAIL, "upload move file to qiniu fail!");
        }
        $adapter = $this->fileQiniu()->getAdapter();
        if ($adapter instanceof QiniuAdapter) {
            return $adapter->getUrl($fileName);
        }
    }
}