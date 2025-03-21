<?php

namespace Tests\Integration\Internal;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationTest extends TestCase
{
    use RefreshDatabase; // Ensures the database is migrated before each test.

    /**
     * Test that the 'users' table exists and contains the expected columns.
     *
     * @return void
     */
    public function test_users_table_has_expected_columns(): void
    {
        // Assert that the 'users' table exists.
        $this->assertTrue(
            Schema::hasTable('users'),
            'The "users" table does not exist.'
        );

        // Define the expected columns for the 'users' table.
        $expectedColumns = [
            'id', 'role_id', 'status_id', 'username', 'email', 'password', 'email_verified_at', 'email_verification_code',
            'email_verification_expires_at', 'remember_token', 'last_login_at', 'email_verified_at', 'created_at', 'updated_at', 'deleted_at',
        ];

        // actual columns:
        $actualColumns = Schema::getColumnListing('users');
        // Assert that the 'users' table contains the expected columns.
        $this->assertTrue(
            Schema::hasColumns('users', $expectedColumns),
            'The "users" table is missing one or more of the expected columns: ' . implode(', ', $expectedColumns) .
            'and the actual columns in the table are: ' . implode(', ', $actualColumns)
        );
    }
    /**
     * Test that the 'roles' table exists and contains the expected columns.
     *
     * @return void
     */
    public function test_roles_table_has_expected_columns(): void
    {
        // Assert that the 'roles' table exists.
        $this->assertTrue(
            Schema::hasTable('roles'),
            'The "roles" table does not exist.'
        );
        // Define the expected columns for the 'roles' table.
        $expectedColumns = [
            'id', 'name', 'description', 'created_at', 'updated_at',
        ];
        // actual columns:
        $actualColumns = Schema::getColumnListing('roles');
        // Assert that the 'roles' table contains the expected columns.
        $this->assertTrue(
            Schema::hasColumns('roles', $expectedColumns),
            'The "roles" table is missing one or more of the expected columns: ' . implode(', ', $expectedColumns) .
            'and the actual columns in the table are: ' . implode(', ', $actualColumns)
        );
    }
    /**
     * Test that the 'statuses' table exists and contains the expected columns.
     *
     * @return void
     */
    public function test_statuses_table_has_expected_columns(): void
    {
        // Assert that the 'statuses' table exists.
        $this->assertTrue(
            Schema::hasTable('statuses'),
            'The "statuses" table does not exist.'
        );
        // Define the expected columns for the 'statuses' table.
        $expectedColumns = [
            'id', 'name', 'description', 'updated_at',
        ];
        // actual columns:
        $actualColumns = Schema::getColumnListing('statuses');
        // Assert that the 'statuses' table contains the expected columns.
        $this->assertTrue(
            Schema::hasColumns('statuses', $expectedColumns),
            'The "statuses" table is missing one or more of the expected columns: ' . implode(', ', $expectedColumns) .
            'and the actual columns in the table are: ' . implode(', ', $actualColumns)
        );
    }

}