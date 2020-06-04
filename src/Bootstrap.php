<?php
declare(strict_types=1);

namespace SamIT\Yii2\StaticAssets;

use yii\base\Application;
use yii\base\BootstrapInterface;

class Bootstrap implements BootstrapInterface
{

    /**
     * Bootstrap method to be called during application bootstrap stage.
     * @param Application $app the application currently running
     */
    public function bootstrap($app): void
    {
        if ($app instanceof \yii\console\Application) {
            if (!$app->hasModule("staticAssets")) {
                $app->setModule("staticAssets", [
                    'class' => Module::class
                ]);
            }
        }
    }
}
