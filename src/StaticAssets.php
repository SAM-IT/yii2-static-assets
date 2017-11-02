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

    public function init()
    {
        parent::init();
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