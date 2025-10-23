<?php

namespace Codinglabs\NotificationSubscriptions\Tests;

use Orchestra\Testbench\TestCase as BaseTestClass;
use Illuminate\Database\Eloquent\Factories\Factory;
use Codinglabs\NotificationSubscriptions\NotificationSubscriptionsServiceProvider;

abstract class TestCase extends BaseTestClass
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Codinglabs\\NotificationSubscriptions\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );

        //        $this->artisan('vendor:publish', ['--tag' => 'roles-migrations'])->run();
        //        $this->artisan('migrate', ['--database' => 'testbench'])->run();
        //        $this->loadLaravelMigrations(['--database' => 'testbench']);
    }

    protected function getPackageProviders($app)
    {
        return [
            NotificationSubscriptionsServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
    }
}
