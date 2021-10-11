<?php
/**
 * This file is part of ZYProSoft/Hyperf-Common.
 *
 * @link     https://topicq.icodefuture.com
 * @document https://topicq.icodefuture.com
 * @contact  1003081775@qq.com;微信:zyprosoft
 * @Company  吉安码动未来信息科技有限公司
 * @license  GPL
 */
declare(strict_types=1);

namespace ZYProSoft\Service;

/**
 * 公开目录的一些文件操作行为的封装
 * Class PublicFileService
 * @package ZYProSoft\Service
 */
class PublicFileService
{
    public function publicRootPath()
    {
        return config('server.settings.document_root');
    }

    public function createPublicDirIfNotExist()
    {
        $publicDir = $this->publicRootPath();
        if (file_exists($publicDir)) {
            if (!is_dir($publicDir)) {
                return false;
            }
            return true;
        }
        return mkdir($publicDir, 0755, true);
    }

    public function createPublicSubDirIfNotExist(string $subDir)
    {
        $subDirPath = $this->publicPath($subDir);
        if (is_null($subDirPath)) {
            return false;
        }
        if (file_exists($subDirPath)){
            if (is_dir($subDirPath)) {
                return true;
            }
            return false;
        }
        return mkdir($subDirPath, 0755, true);
    }

    public function publicPath(string $subPath)
    {
        $result = $this->createPublicDirIfNotExist();
        if (!$result) {
            return null;
        }
        return $this->publicRootPath().$subPath;
    }

    public function deletePublicPath(string $subPath)
    {
        $fullPath = $this->publicPath($subPath);
        if (!file_exists($fullPath)) {
            return true;
        }
        if (is_dir($fullPath)) {
            return rmdir($fullPath);
        }
        return unlink($fullPath);
    }
}