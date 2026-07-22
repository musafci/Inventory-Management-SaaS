<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Concerns\InteractsWithOrganizations;
use Tests\Concerns\InteractsWithPassport;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithOrganizations;
    use InteractsWithPassport;

    /** @var class-string<\Illuminate\Database\Seeder> */
    protected $seeder = \Database\Seeders\PlanSeeder::class;

    protected bool $seed = true;
}
