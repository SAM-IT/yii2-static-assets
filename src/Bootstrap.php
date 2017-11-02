<?php


namespace SamIT\Yii2\StaticAssets;


use yii\base\Application;
use yii\base\BootstrapInterface;

class Bootstrap implements BootstrapInterface
{

    /**
     * Bootstrap method to be called during application bootstrap stage.
     * @param Application $app the application currently running
     */
    public function bootstrap($app)
    {
        if ($app instanceof \yii\console\Application) {
            $app->setModule("staticAssets", [
                'class' => StaticAssets::class
            ]);
            $app->getModule('staticAssets');
        }

        if ($app instanceof \yii\web\Application) {
            $app->assetManager->hashCallback = StaticAssets::hashCallback();
        }
    }
}