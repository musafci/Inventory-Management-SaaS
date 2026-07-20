<?php

use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setUpPassport();
    $this->seed(\Database\Seeders\PlatformPassportSeeder::class);
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

test('demo seeder creates organizations with stock', function () {
    $this->seed(DemoSeeder::class);

    $this->assertDatabaseHas('organizations', ['slug' => 'acme-warehouse']);
    $this->assertDatabaseHas('organizations', ['slug' => 'beta-retail']);
    $this->assertDatabaseHas('users', ['email' => 'consultant@demo.test']);
    $this->assertDatabaseHas('stocks', ['quantity_on_hand' => 20]);
});
