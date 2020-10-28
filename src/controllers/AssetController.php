<?php
declare(strict_types=1);

namespace SamIT\Yii2\StaticAssets\controllers;

use SamIT\Yii2\StaticAssets\helpers\AssetHelper;
use SamIT\Yii2\StaticAssets\Module;
use yii\console\Controller;
use yii\helpers\Console;
use yii\helpers\FileHelper;
use yii\web\AssetBundle;
use yii\web\AssetManager;

/**
 * Class AssetController
 * @package SamIT\Yii2\StaticAssets\controllers
 * @property Module $module
 */
class AssetController extends Controller
{
    public $defaultBundle;

    public $baseUrl;

    /**
     * @var string The location of your entry script inside the PHPFPM container.
     * Must be absolute, does not support aliases.
     */
    public $entryScript;


    /** @var array List of fnmatch patterns with file names to skip. */
    public $excludedPatterns = [];

    /** @var array Application path to publish assets. */
    public $applicationPath;

    public function init(): void
    {
        parent::init();
        $this->defaultBundle = $this->module->defaultBundle;
        $this->baseUrl = $this->module->baseUrl;
        $this->excludedPatterns = $this->module->excludedPatterns;
        $this->applicationPath = $this->module->applicationPath;
    }


    public function actionPublish($path): void
    {
        $this->stdout("Publishing default bundle to webroot...\n", Console::FG_CYAN);
        if (isset($this->defaultBundle)) {
            $class = $this->defaultBundle;
            /** @var AssetBundle $bundle */
            $bundle = new $class;
            $bundle->publish($this->getAssetManager($path));
            $this->stdout("Copying {$bundle->basePath} to {$path}/default...\n", Console::FG_CYAN);
            echo "$bundle->basePath";

            \passthru("ls -la {$bundle->basePath}");
            FileHelper::copyDirectory($bundle->basePath, "$path/default");
            AssetHelper::createGzipFiles("$path/default");


            $this->stdout("OK\n", Console::FG_GREEN);
        } else {
            \mkdir("$path/default");
        }

        $assetManager = $this->getAssetManager($path);
        $this->stdout("Publishing application ({$this->applicationPath}) assets... ", Console::FG_CYAN);
        AssetHelper::publishAssets($assetManager, \Yii::getAlias($this->applicationPath), $this->excludedPatterns);
        $this->stdout("OK\n", Console::FG_GREEN);

        $this->stdout("Publishing vendor assets... ", Console::FG_CYAN);
        AssetHelper::publishAssets($assetManager, \Yii::getAlias('@vendor'), $this->excludedPatterns);
        $this->stdout("OK\n", Console::FG_GREEN);


        $this->stdout("Compressing assets... ", Console::FG_CYAN);
        AssetHelper::createGzipFiles($path);
        $this->stdout("OK\n", Console::FG_GREEN);
    }

    protected function getAssetManager($fullPath): AssetManager
    {
        $this->stdout("Creating asset path: $fullPath... ", Console::FG_CYAN);
        if (!\is_dir($fullPath)) {
            \mkdir($fullPath, 0777, true);
        }
        $this->stdout("OK\n", Console::FG_GREEN);
        // Override some configuration.
        $assetManagerConfig = $this->module->getComponents()['assetManager'];
        $assetManagerConfig['basePath'] = $fullPath;
        $assetManagerConfig['baseUrl'] = $this->baseUrl;
        $this->module->set('assetManager', $assetManagerConfig);
        return $this->module->get('assetManager');
    }
}
