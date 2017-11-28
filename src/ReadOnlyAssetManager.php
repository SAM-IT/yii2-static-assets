<?php


namespace SamIT\Yii2\StaticAssets;


use Yii;

/**
 * Class ReadOnlyAssetManager
 * Asset manager that does not actualy (re)publish files. Use it in production when the assets are part of the nginx
 * container
 * @package SamIT\Yii2\StaticAssets
 */
class ReadOnlyAssetManager extends \yii\web\AssetManager
{
    public function init()
    {
        $this->basePath = Yii::getAlias($this->basePath);
        $this->baseUrl = rtrim(Yii::getAlias($this->baseUrl), '/');
    }

    protected function publishFile($src)
    {
        $dir = $this->hash($src);
        $fileName = basename($src);
        $dstDir = $this->basePath . DIRECTORY_SEPARATOR . $dir;
        $dstFile = $dstDir . DIRECTORY_SEPARATOR . $fileName;
        return [$dstFile, $this->baseUrl . "/$dir/$fileName"];
    }

    protected function publishDirectory($src, $options)
    {
        $dir = $this->hash($src);
        $dstDir = $this->basePath . DIRECTORY_SEPARATOR . $dir;
        return [$dstDir, $this->baseUrl . '/' . $dir];
    }

    protected function hash($path)
    {
        return call_user_func(StaticAssets::hashCallback(), $path);
    }


}