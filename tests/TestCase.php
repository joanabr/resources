<?php

declare(strict_types = 1);

namespace Tests;


use HoneyComb\Resources\Providers\HCResourceServiceProvider;
use Illuminate\Foundation\Application;

/**
 * Class TestCase
 * @package Tests
 */
abstract class TestCase extends \Orchestra\Testbench\BrowserKit\TestCase
{
    /**
     *
     */
    protected function setUp()
    {
        parent::setUp();
    }

    /**
     * @param Application $app
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        return [
            HCResourceServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);
    }
}
