<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Indicates whether the default seeder should run before each test in classes that use RefreshDatabase trait.
     *
     * @var bool
     */
    protected $seed = true;
}