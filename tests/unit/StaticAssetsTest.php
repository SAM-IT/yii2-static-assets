<?php

declare(strict_types=1);
namespace tests;

class StaticAssetsTest extends \Codeception\Test\Unit
{
    public function testNginxBlock(): void
    {
        /** @var \SamIT\Yii2\StaticAssets\StaticAssets $module */
//        $module = \Yii::$app->getModule('staticAssets');
//        fwrite(\STDOUT, $module->getNginxConfig());
//        die();
    }

    public function testNginxConfiguration(): void
    {
        /** @var \SamIT\Yii2\StaticAssets\StaticAssets $module */
        $module = \Yii::$app->getModule('staticAssets');

//        $nginxConfig = $module->getNginxConfig();
    }
}
