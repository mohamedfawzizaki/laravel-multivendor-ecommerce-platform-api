<?php

namespace Tests\Integration\Internal;

use Tests\TestCase;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Database\Seeders\UsersSeeder;
use Illuminate\Support\Facades\DB;
use Database\Factories\RoleFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SeederTest extends TestCase
{
    use RefreshDatabase; // Ensure a clean, migrated database for each test

    /**
     * Test that the main DatabaseSeeder populates the 'users' table as expected.
     *
     * @return void
     */
    public function test_database_seeder_populates_users(): void
    {
        // Run the main DatabaseSeeder, which might call other seeders such as RoleSeeder and StatusSeeder.
        $this->seed();

        $expectedUser = DB::table('users')->first();
        $this->assertDatabaseHas('users', ['username' => $expectedUser->username]);
        $this->assertDatabaseHas('roles', ['name' => 'admin']);
        $this->assertDatabaseHas('statuses', ['name' => 'active']);
    }
    /**
     * Test that the RoleSeeder populates the 'roles' table as expected.
     *
     * @return void
     */
    public function test_user_seeder_populates_roles(): void
    {
        // Run the UserSeeder
        $this->seed(UsersSeeder::class);

        $expectedUser = DB::table('users')->first();
        $this->assertDatabaseHas('users', ['username' => $expectedUser->username]);
    }
    /**
     * Test that the RoleSeeder populates the 'roles' table as expected.
     *
     * @return void
     */
    public function test_role_seeder_populates_roles(): void
    {
        // Run the RoleSeeder
        $this->seed(\Database\Seeders\RolesSeeder::class);

        // Assert that the 'roles' table has the expected columns (as verified in MigrationTest)
        $this->assertDatabaseHas('roles', ['name' => 'admin']);
        $this->assertDatabaseHas('roles', ['name' => 'customer']);
        $this->assertDatabaseHas('roles', ['name' => 'seller']);
        $this->assertDatabaseHas('roles', ['name' => 'employee']);
    }

    /**
     * Test that the StatusSeeder populates the 'statuses' table as expected.
     *
     * @return void
     */
    public function test_status_seeder_populates_statuses(): void
    {
        // Run the StatusSeeder
        $this->seed(\Database\Seeders\StatusesSeeder::class);
        // Assert that the 'statuses' table has the expected columns and data.
        // For example, we expect a status with name 'active'.
        $this->assertDatabaseHas('statuses', ['name' => 'active']);
        $this->assertDatabaseHas('statuses', ['name' => 'inactive']);
        $this->assertDatabaseHas('statuses', ['name' => 'banned']);
    }
}