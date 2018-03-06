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

    /**
     * @var Docker
     */
    protected $docker;

    /**
     * @var string the user to authenticate against the repository
     */
    public $user;

    /**
     * @var string the password to authenticate against the repository
     */
    public $password;

    public function init(): void
    {
        parent::init();
        $this->docker = Docker::create();
        $this->push = $this->module->push;
        $this->image = $this->module->image;
        $this->tag = $this->module->tag;
    }

    public function actionBuild(): void
    {
        if ($this->push && !isset($this->image, $this->user, $this->password)) {
            throw new InvalidConfigException("When using the push option, you must configure or provide user, password and image");
        }

        $params = [];


        if (isset($this->image)) {
            $name = "{$this->image}:{$this->tag}";
            $params['t'] = $name;
        }
        $buildStream = $this->createBuildStream($params);
        $this->color = true;
        $buildStream->onFrame(function(BuildInfo $buildInfo): void {
            $this->stdout($buildInfo->getStream(), Console::FG_CYAN);
            $this->stdout($buildInfo->getProgress(), Console::FG_YELLOW);
            $this->stdout($buildInfo->getStatus(), Console::FG_RED);
            if (!empty($buildInfo->getProgressDetail())) {
                $this->stdout($buildInfo->getProgressDetail()->getMessage(), Console::FG_YELLOW);
            }
            if (!empty($buildInfo->getErrorDetail())) {
                $this->stdout($buildInfo->getErrorDetail()->getCode() . ':' . $buildInfo->getErrorDetail()->getMessage(), Console::FG_YELLOW);
            }
            if (!empty($buildInfo->getError())) {
                throw new \Exception($buildInfo->getError() . ':' . $buildInfo->getErrorDetail()->getMessage());
            }
        });
        $buildStream->wait();
        $this->stdout("Wait finished\n");
        $buildStream->wait();

        if ($this->push) {
            $authConfig = new AuthConfig();
            $authConfig->setUsername($this->user);
            $authConfig->setPassword($this->password);
            $params = [
                'X-Registry-Auth' => $authConfig
            ];
            /** @var PushStream $pushStream */
            $pushStream = $this->docker->imagePush($name, [], $params ?? [],  Docker::FETCH_OBJECT);

            if ($pushStream instanceof ResponseInterface) {
                throw new \Exception($pushStream->getReasonPhrase() . ':' . $pushStream->getBody()->getContents(), $pushStream->getStatusCode());
            }

            $pushStream->onFrame(function(PushImageInfo $pushImageInfo): void {
                if (!empty($pushImageInfo->getError())) {
                    throw new \Exception($pushImageInfo->getError());
                }
                $this->stdout($pushImageInfo->getProgress(), Console::FG_YELLOW);
                $this->stdout($pushImageInfo->getStatus(), Console::FG_RED);
            });
            $pushStream->wait();
        }
    }

    public function createBuildStream(array $params = []): BuildStream
    {

        $context = $this->module->createBuildContext();
        return $this->docker->imageBuild($context->toStream(), $params, [], Docker::FETCH_OBJECT);
    }

    public function options($actionID)
    {

        $result = parent::options($actionID);
        switch ($actionID) {
            case 'build':
                $result[] = 'push';
                $result[] = 'image';
                $result[] = 'tag';
                $result[] = 'user';
                $result[] = 'password';
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
        $result['u'] = 'user';
        $result['P'] = 'password';
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