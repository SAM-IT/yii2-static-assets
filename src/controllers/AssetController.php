<?php


namespace SamIT\Yii2\StaticAssets\controllers;


use SamIT\Yii2\StaticAssets\helpers\AssetHelper;
use SamIT\Yii2\StaticAssets\StaticAssets;
use yii\console\Controller;
use yii\helpers\Console;
use yii\helpers\FileHelper;
use yii\web\AssetBundle;
use yii\web\AssetManager;

/**
 * Class AssetController
 * @package SamIT\Yii2\StaticAssets\controllers
 * @property StaticAssets $module
 */
class AssetController extends Controller
{
    public function actionIndex($path)
    {
        $fullPath = getcwd() . "/$path";
        $this->stdout("Creating path: " . $fullPath);
        mkdir($fullPath, 0777, true);
        $assetManager = new AssetManager([
            'basePath' => $fullPath,
            'baseUrl' => $this->module->baseUrl,
            'hashCallback' => StaticAssets::hashCallback()
        ]);

        AssetHelper::publishAssets($assetManager, \Yii::getAlias('@app'));
        AssetHelper::createGzipFiles($fullPath);

    }

    public function actionBuildContainer()
    {
        $dockerContext = \Yii::getAlias('@runtime') . '/build' . time();

        $fullPath = $dockerContext . "/assets";
        $this->stdout("Creating asset path: $fullPath... ", Console::FG_CYAN);
        mkdir($fullPath, 0777, true);
        $this->stdout("OK\n", Console::FG_GREEN);
        $assetManager = new AssetManager([
            'basePath' => $fullPath,
            'baseUrl' => $this->module->baseUrl,
            'hashCallback' => StaticAssets::hashCallback()
        ]);

        $this->stdout("Publishing assets... ", Console::FG_CYAN);
        AssetHelper::publishAssets($assetManager, \Yii::getAlias('@app'));
        $this->stdout("OK\n", Console::FG_GREEN);

        $this->stdout("Compressing assets... ", Console::FG_CYAN);
        AssetHelper::createGzipFiles($fullPath);
        $this->stdout("OK\n", Console::FG_GREEN);

        $this->stdout("Copying build context... ", Console::FG_CYAN);
        FileHelper::copyDirectory(\Yii::getAlias('@SamIT/Yii2/StaticAssets/docker'), $dockerContext);
        $this->stdout("OK\n", Console::FG_GREEN);

        $this->stdout("Starting build...\n", Console::FG_CYAN);
        passthru(strtr('docker build --pull -t {name} {path}', [
            '{path}' => $dockerContext,
            '{name}' => $this->module->containerTag ?? "''"
        ]));
        $this->stdout("OK\n", Console::FG_GREEN);


    }
}