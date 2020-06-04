<?php
declare(strict_types=1);
$dir = __DIR__;
while (!\file_exists($dir . '/vendor/autoload.php') && $dir !== '/') {
    $dir = \dirname($dir);
}
if (!\file_exists($dir . '/vendor/autoload.php')) {
    die("Composer autoloader not found");
}

require_once $dir . '/vendor/autoload.php';
\define('YII_DEBUG', 1);
class Yii extends \yii\BaseYii
{
};
\Yii::$container = new \yii\di\Container();
$application = new \yii\console\Application([
    'id' => 'yii2-phpfpm-test',
    'basePath' => __DIR__,
    'aliases' => [
        '@SamIT/Yii2/StaticAssets' => __DIR__
    ],
    'modules' => [
        'staticAssets' => [
            'class' => \SamIT\Yii2\StaticAssets\Module::class,
            'push' => true,
            'image' => \md5(\random_bytes(5))
        ]
    ]
]);
die($application->run());
