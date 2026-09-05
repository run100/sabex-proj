<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = require Application::inferBasePath().'/bootstrap/app.php';
        $this->traitsUsedByTest = class_uses_recursive(static::class);

        $app->booting(function (Application $app): void {
            if (method_exists($this, 'defineEnvironment')) {
                $this->defineEnvironment($app);
            }
        });

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
