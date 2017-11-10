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
            if (!$app->hasModule("staticAssets")) {
                $app->setModule("staticAssets", [
                    'class' => StaticAssets::class
                ]);
            }
        }

        // Update or set configuration for asset manager.
        if ($app instanceof \yii\web\Application) {
            if ($app->has('assetManager', true)) {
                $app->get('assetManager')->hashCallback = StaticAssets::hashCallback();
            } elseif ($app->has('assetManager')) {
                $config = $app->getComponents(true)['assetManager'];
                $config['hashCallback'] = StaticAssets::hashCallback();
                $app->set('assetManager', $config);
            }
        }

    }
}