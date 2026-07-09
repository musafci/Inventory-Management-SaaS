<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Concerns\InteractsWithOrganizations;
use Tests\Concerns\InteractsWithPassport;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithOrganizations;
    use InteractsWithPassport;
}
