<?php


namespace SamIT\Yii2\StaticAssets;


use yii\base\Module;
use yii\console\Application;

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

    /**
     * @var string The location if your entry script inside your PHP-FPM container / server
     * Does not support aliases, must be absolute.
     */
    public $entryScript;

    public function init()
    {
        parent::init();
        $assetManagerConfig = $this->module->getComponents(true)['assetManager'] ?? [];
        $assetManagerConfig['hashCallback'] = self::hashCallback();
        if ($this->module instanceof Application) {
            if (!isset(\Yii::$aliases['@webroot'])) {
                \Yii::setAlias('@webroot', sys_get_temp_dir());
            }
            $assetManagerConfig['basePath'] = sys_get_temp_dir();
        }
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