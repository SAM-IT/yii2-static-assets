<?php
declare(strict_types=1);

namespace SamIT\Yii2\StaticAssets\controllers;

use Docker\API\Model\AuthConfig;
use Docker\API\Model\BuildInfo;
use Docker\API\Model\PushImageInfo;
use Docker\Docker;
use Docker\Stream\BuildStream;
use Docker\Stream\PushStream;
use Psr\Http\Message\ResponseInterface;
use SamIT\Yii2\StaticAssets\Module;
use yii\base\InvalidConfigException;
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
     * @var string The name of the created image
     * If not explicitly set will take its default from module config.
     */
    public $image;

    /**
     * @var string The tag of the created image
     * If not explicitly set will take its default from module config.
     */
    public $tag;

    /**
     * @var bool whether to push the image after a successful build.
     * If not explicitly set will take its default from module config.
     */
    public $push;

    public function init(): void
    {
        parent::init();
        $this->push = $this->module->push;
        $this->image = $this->module->image;
        $this->tag = $this->module->tag;
    }

    public function actionBuild(): void
    {
        if ($this->push && !isset($this->image, $this->user, $this->password)) {
            throw new InvalidConfigException("When using the push option, you must configure or provide user, password and image");
        }

        $name = "{$this->image}:{$this->tag}";

        $docker = new \SamIT\Docker\Docker();
        $context =  $this->module->createBuildContext();
        $this->color = true;
        $docker->build($context, $name);



        if ($this->push) {
            $docker->push($name);
        }
    }


    public function options($actionID)
    {

        $result = parent::options($actionID);
        switch ($actionID) {
            case 'build':
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

    public function stdout($string)
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
