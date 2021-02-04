<?php


namespace ZYProSoft\Service;


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