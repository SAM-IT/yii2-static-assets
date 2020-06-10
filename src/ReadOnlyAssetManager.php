<?php
declare(strict_types=1);

namespace SamIT\Yii2\StaticAssets;

use Yii;
use yii\base\NotSupportedException;

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
        if ($this->assetDevelopmentMode) {
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
        throw new NotSupportedException('Publishing files is not supported, publish directories instead');
    }

    public function publish($path, $options = [])
    {
        if ($this->assetDevelopmentMode) {
            return parent::publish($path, $options);
        }

        return $this->publishDirectory($path, $options);
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
        return \call_user_func(Module::hashCallback(), $path);
    }
}
