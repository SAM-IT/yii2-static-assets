<?php
declare(strict_types=1);

use Codeception\Test\Unit;

class ModuleBootstrapTest extends Unit
{
    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testModuleLoaded(): void
    {
        $modules = \Yii::$app->getModules();
        $this->assertNotEmpty($modules);
    }
}