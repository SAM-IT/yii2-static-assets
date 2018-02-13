<?php
declare(strict_types=1);

namespace SamIT\Yii2\StaticAssets\controllers;


use SamIT\Yii2\StaticAssets\helpers\AssetHelper;
use SamIT\Yii2\StaticAssets\Module;
use yii\console\Controller;
use yii\helpers\Console;
use yii\web\AssetManager;

/**
 * Class AssetController
 * @package SamIT\Yii2\StaticAssets\controllers
 * @property Module $module
 */
class AssetController extends Controller
{
    /**
     * @var bool Whether to push the image after a successful build.
     * If not explicitly set will take its default from module config.
     */
    public $push;

    /**
     * @var string The name of the created image
     * If not explicitly set will take its default from module config.
     */
    public $image;

    /**
     * @var string The tag of the created image
     * If not explicitly set will take its default from module config.
     */
    public $tag;

    /**
     * @var string The default asset bundle
     * If not explicitly set will take its default from module config.
     */
    public $defaultBundle;

    public $baseUrl;

    /**
     * @var string The location of your entry script inside the PHPFPM container.
     * Must be absolute, does not support aliases.
     */
    public $entryScript;


    /** @var array List of fnmatch patterns with file names to skip. */
    public $excludedPatterns = [];


    public function init(): void
    {
        parent::init();
        $this->push = $this->module->push;
        $this->image = $this->module->image;
        $this->tag = $this->module->tag;
        $this->defaultBundle = $this->module->defaultBundle;
        $this->baseUrl = $this->module->baseUrl;
        $this->entryScript = $this->module->entryScript;
        $this->excludedPatterns = $this->module->excludedPatterns;
    }


    public function actionPublish($path): void
    {
        $assetManager = $this->getAssetManager($path);
        $this->stdout("Publishing assets... ", Console::FG_CYAN);
        AssetHelper::publishAssets($assetManager, \Yii::getAlias('@app'));
        $this->stdout("OK\n", Console::FG_GREEN);
        $this->stdout("Compressing assets... ", Console::FG_CYAN);
        AssetHelper::createGzipFiles($path);
        $this->stdout("OK\n", Console::FG_GREEN);
    }

    protected function getAssetManager($fullPath): AssetManager
    {
        $this->stdout("Creating asset path: $fullPath... ", Console::FG_CYAN);
        \mkdir($fullPath, 0777, true);
        $this->stdout("OK\n", Console::FG_GREEN);
        // Override some configuration.
        $assetManagerConfig = $this->module->getComponents()['assetManager'];
        $assetManagerConfig['basePath'] = $fullPath;
        $assetManagerConfig['baseUrl'] = $this->baseUrl;
        $this->module->set('assetManager', $assetManagerConfig);
        return $this->module->get('assetManager');
    }
}