<?php
declare(strict_types=1);
return [
    'class' => \yii\console\Application::class,
    'id' => 'yii2-phpfpm-test',
    'basePath' => __DIR__ . '/../src',
    'extensions' => [
        [
            'name' => 'yii2-phpfpm',
            'version' => 'test',
            'bootstrap' => \SamIT\Yii2\PhpFpm\ModuleBootstrap::class,
//            'alias' =>
        ]

    ]
];