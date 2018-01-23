<?php
declare(strict_types=1);

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
    /**
     * @var bool Whether to enable asset development mode.
     */
    public $assetDevelopmentMode = false;

    public function init(): void
    {
        if ($this->assetDevelopmentMode)
        {
            $this->baseUrl = '/dev-assets';
            $this->basePath = '/tmp/assets';
            $this->forceCopy = true;
            return;
        }
        $this->basePath = Yii::getAlias($this->basePath);
        $this->baseUrl = \rtrim(Yii::getAlias($this->baseUrl), '/');

    }

    protected function publishFile($src)
    {
        if ($this->assetDevelopmentMode) {
            return parent::publishFile($src);
        }

        $dir = $this->hash($src);
        $fileName = \basename($src);
        $dstDir = $this->basePath . DIRECTORY_SEPARATOR . $dir;
        $dstFile = $dstDir . DIRECTORY_SEPARATOR . $fileName;
        return [$dstFile, $this->baseUrl . "/$dir/$fileName"];
    }

    protected function publishDirectory($src, $options)
    {
        if ($this->assetDevelopmentMode) {
            return parent::publishDirectory($src, $options);
        }

        $dir = $this->hash($src);
        $dstDir = $this->basePath . DIRECTORY_SEPARATOR . $dir;
        return [$dstDir, $this->baseUrl . '/' . $dir];
    }

    protected function hash($path)
    {
        return \call_user_func(StaticAssets::hashCallback(), $path);
    }


}