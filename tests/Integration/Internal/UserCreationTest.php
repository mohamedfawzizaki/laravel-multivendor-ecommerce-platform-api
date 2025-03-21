<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTruncation;

class UserCreationTest extends TestCase
{
    use RefreshDatabase;    // Laravel will wrap your tests in a transaction that is automatically rolled back at the end of each test. 
    // use DatabaseMigrations; // Runs migrations before each test and rolls them back afterward
    // use DatabaseTruncation; // involves truncating (i.e., deleting all rows from) all database tables between tests. This does not re-run the migrations; instead, it cleans out all data, leaving the schema intact.

    /**
     * Test that the database is seeded correctly and a user can be created.
     *
     * @return void
     */
    public function test_user_is_created_in_database()
    {
        // Seed the database using DatabaseSeeder or a specific seeder, By default, the seed() method calls DatabaseSeeder.
        $this->seed();

        // Optionally, you can seed a specific seeder:
        // $this->seed(\Database\Seeders\RoleSeeder::class);

        // you can assert that a specific role is present.
        $this->assertDatabaseHas('roles', [
            'name' => 'admin',
        ]);
    }
}