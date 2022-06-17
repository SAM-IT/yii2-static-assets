<?php

declare(strict_types=1);
namespace tests;

use Codeception\Test\Unit;

class ModuleBootstrapTest extends Unit
{
    protected function _before(): void
    {
    }

    protected function _after(): void
    {
    }

    // tests
    public function testModuleLoaded(): void
    {
        $modules = \Yii::$app->getModules();
        $this->assertNotEmpty($modules);
    }
}
