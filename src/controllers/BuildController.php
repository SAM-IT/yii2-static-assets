<?php

declare(strict_types=1);

namespace SamIT\Yii2\StaticAssets\controllers;

use SamIT\Yii2\StaticAssets\Module;
use Symfony\Component\Filesystem\Filesystem;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * Class BuildController
 * @package SamIT\Yii2\PhpFpm\controllers
 * @property Module $module
 */
class BuildController extends Controller
{
    public $defaultAction = 'build';

    /**
     * @param string $targetPath The path where the docker build context should be stored
     */
    public function actionCreateContext(string $targetPath): void
    {
        $filesystem = new Filesystem();
        if (!is_dir($targetPath)) {
            $filesystem->mkdir($targetPath);
        }

        $context = $this->module->createBuildContext();
        $filesystem->mirror($context->getDirectory(), $targetPath);
    }

    public function stdout($string): int
    {
        if ($this->isColorEnabled()) {
            $args = \func_get_args();
            \array_shift($args);
            $string = Console::ansiFormat($string, $args);
        }

        echo $string;
        return \strlen($string);
    }
}
