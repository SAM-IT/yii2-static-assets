<?php
declare(strict_types=1);
namespace tests;

use Docker\API\Model\BuildInfo;
use Docker\API\Normalizer\NormalizerFactory;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

class ModuleTest extends \Codeception\Test\Unit
{
    /**
     * @var \SamIT\Yii2\StaticAssets\Module
     */
    protected $module;

    public function _before(): void
    {
        parent::_before();
        $this->module = \Yii::$app->getModule('staticAssets');
        $this->assertInstanceOf(\SamIT\Yii2\StaticAssets\Module::class, $this->module);
    }

    // tests
    public function testBuild(): void
    {
        $context = $this->module->createBuildContext();
        $directory = $context->getDirectory();

        $dockerFile = file_get_contents("$directory/Dockerfile");

        $fileName = \preg_replace('#.*ADD (.+?) /nginx\.conf.*#s', '$1', $dockerFile);
        $this->assertFileExists($directory . '/' . $fileName);
//        passthru("docker build \"$directory\"");

//        $fileName = \preg_replace('#.*ADD (.+?) /entrypoint\.sh.*#s', '$2', $dockerFile);
//        $this->assertFileExists($directory . '/' . $fileName);
    }
}
