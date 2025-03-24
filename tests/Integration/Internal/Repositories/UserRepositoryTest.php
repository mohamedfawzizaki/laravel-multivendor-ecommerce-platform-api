<?php

namespace Tests\Integration\Internal;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Repositories\Repos\UserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The UserRepository
     */
    protected $userRepository;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Initialize the UserRepository.
        // This assumes you have a repository class at App\Repositories\Eloquent\UserRepository.
        $this->userRepository = new UserRepository(new User());
    }

    /**
     * Test that the repository retrieves all users.
     *
     * @return void
     */
    public function test_it_retrieves_all_users(): void
    {
        // get all users using query buider
        $users = DB::table('users')->get();

        // Use the repository method to get all users.
        $retrievedUsers = $this->userRepository->getAll();

        // Assert that the repository returns 5 users.
        $this->assertCount($users->count(), $retrievedUsers);

        // Optionally, check that the IDs of the created users match those returned.
        $createdIds = $users->pluck('id')->sort()->values();
        $retrievedIds = collect($retrievedUsers)->pluck('id')->sort()->values();
        $this->assertEquals($createdIds, $retrievedIds);
    }

    /**
     * Test that the repository retrieves a user by its ID.
     *
     * @return void
     */
    public function test_it_retrieves_a_user_by_id(): void
    {
        // Create a single user.
        $user = User::factory()->create();

        // Retrieve the user using the repository.
        $retrievedUser = $this->userRepository->findById($user->id);

        // Assert that the user was retrieved.
        $this->assertNotNull($retrievedUser);

        // Assert that the retrieved user's ID matches the created user's ID.
        $this->assertEquals($user->id, $retrievedUser->id);
    }

    /**
     * Test that the repository returns null when a user does not exist.
     *
     * @return void
     */
    public function test_it_returns_null_for_non_existing_user(): void
    {
        // Attempt to retrieve a user with a non-existing ID.
        $retrievedUser = $this->userRepository->findById(99999);

        // Assert that the repository returns null.
        $this->assertNull($retrievedUser);
    }
}