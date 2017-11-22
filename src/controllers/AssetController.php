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

    public function init()
    {
        parent::init();
        $this->push = $this->module->push;
        $this->image = $this->module->image;
        $this->tag = $this->module->tag;
    }


    public function actionIndex($path)
    {
        $fullPath = getcwd() . "/$path";
        $this->stdout("Creating path: " . $fullPath);
        mkdir($fullPath, 0777, true);
        $assetManager = $this->module->get('assetManager');
        $assetManager->basePath = $fullPath;
        $assetManager->baseUrl = $this->module->baseUrl;

        AssetHelper::publishAssets($assetManager, \Yii::getAlias('@app'));
        AssetHelper::createGzipFiles($fullPath);

    }

    /**
     * Builds a docker container that contains the assets and optionally pushes it.
     * @throws \yii\base\ErrorException
     */
    public function actionBuildContainer()
    {
        $buildDir = \Yii::getAlias('@runtime') . '/build' . time();

        $fullPath = $buildDir . "/assets";
        $this->stdout("Creating asset path: $fullPath... ", Console::FG_CYAN);
        mkdir($fullPath, 0777, true);
        $this->stdout("OK\n", Console::FG_GREEN);
        $assetManager = $this->module->get('assetManager');
        $assetManager->basePath = $fullPath;
        $assetManager->baseUrl = $this->module->baseUrl;

        $this->stdout("Publishing assets... ", Console::FG_CYAN);
        AssetHelper::publishAssets($assetManager, \Yii::getAlias('@app'));
        $this->stdout("OK\n", Console::FG_GREEN);

        $this->stdout("Compressing assets... ", Console::FG_CYAN);
        AssetHelper::createGzipFiles($fullPath);
        $this->stdout("OK\n", Console::FG_GREEN);

        $this->stdout("Copying build context... ", Console::FG_CYAN);
        FileHelper::copyDirectory(\Yii::getAlias('@SamIT/Yii2/StaticAssets/docker'), $buildDir);
        $this->stdout("OK\n", Console::FG_GREEN);

        $this->stdout("Starting build...\n", Console::FG_CYAN);
        $command = strtr('docker build --pull {name} {path}', [
            '{path}' => $buildDir,
            '{name}' => $this->image ? "-t {$this->image}:{$this->tag}" : ""
        ]);
        $this->stdout($command . "\n", Console::FG_YELLOW);
        passthru($command, $retval);
        if ($retval !== 0) {
            $this->stderr("FAIL\nDocker build failed, leaving build folder intact for inspection\n", Console::FG_RED);
            return;
        }

        $this->stdout("OK\n", Console::FG_GREEN);
        $this->stdout("Removing build folder...", Console::FG_CYAN);
        $this->stdout("OK\n", Console::FG_GREEN);

        if (!($this->push && $this->image)) {
            return;
        }

        $this->stdout("Pushing image", Console::FG_CYAN);
        $command = strtr('docker push {name}', [
            '{name}' => $this->image . ':' . $this->tag
        ]);
        $this->stdout($command . "\n", Console::FG_YELLOW);
        passthru($command, $retval);

        if ($retval !== 0) {
            $this->stderr("FAIL\nDocker push failed\n", Console::FG_RED);
            return;
        }

        $this->stdout("OK\n", Console::FG_GREEN);
    }

    public function options($actionID)
    {

        $result = parent::options($actionID);
        switch ($actionID) {
            case 'build-container':
                $result[] = 'push';
                $result[] = 'image';
                $result[] = 'tag';
                break;

        }
        return $result;
    }

    public function optionAliases()
    {
        $result = parent::optionAliases();
        $result['p'] = 'push';
        $result['t'] = 'tag';
        $result['i'] = 'image';
        return $result;
    }


}