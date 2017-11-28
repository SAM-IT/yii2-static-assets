<?php


namespace SamIT\Yii2\StaticAssets;


use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\Module;

class StaticAssets extends Module
{
    /**
     * @var string The base URL for the assets. This can include a hostname.
     */
    public $baseUrl;

    public $image;
    public $tag = 'latest';

    public $push = false;

    /**
     * @var string The class of the default asset bundle. This will be used to look for files like /favicon.ico
     */
    public $defaultBundle;

    public function init()
    {
        parent::init();
        $assetManagerConfig = $this->module->getComponents(true)['assetManager'] ?? [];
        $assetManagerConfig['hashCallback'] = self::hashCallback();
        $this->set('assetManager', $assetManagerConfig);

    }

    public static function hashCallback(): \Closure
    {
        return function($path) {
            $dir = is_file($path) ? dirname($path) : $path;
            $relativePath = strtr($dir, [\Yii::getAlias('@app') => '']);
            return strtr(trim($relativePath, '/'), ['/' => '_']);
        };
    }



}