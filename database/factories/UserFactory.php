<?php

// Define the namespace for the factory.
namespace Database\Factories;

// Import the necessary model classes.
use App\Models\Role;
use App\Models\User;
use App\Models\Phone;
use App\Models\Profile;
use App\Models\Status;

// Import helper classes for generating strings and hashing passwords.
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

// Import the base Factory class from Laravel.
use Illuminate\Database\Eloquent\Factories\Factory;

// Import external factories to use their sequences.
use Database\Factories\RoleFactory;
use Database\Factories\StatusFactory;

/**
 * This factory is responsible for generating User model instances.
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The model that this factory creates.
     *
     * @var class-string<User>
     */
    protected $model = User::class; // Specify that this factory creates User instances.

    /**
     * The current password being used by the factory.
     *
     * @var string|null
     */
    protected static ?string $password = null; // Cache the hashed password to avoid rehashing on every creation.

    /**
     * Cache a default role ID for all users created by the factory.
     *
     * @var int|null
     */
    protected static ?int $defaultRoleId = null; // Static variable to store the default role ID.

    /**
     * Cache a default status ID for all users created by the factory.
     *
     * @var int|null
     */
    protected static ?int $defaultStatusId = null; // Static variable to store the default status ID.

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),                     // Generate a unique UUID for the user's primary key.
            'role_id' => $this->getRoleId(),                  // Set the user's role using the cached default role ID.
            'status_id' => $this->getStatusId(),              // Set the user's status using the cached default status ID.
            
            'name' => $this->faker->unique()->userName(), // Generate a unique name using Faker.
            'email' => $this->faker->unique()->safeEmail(),   // Generate a unique safe email address using Faker.
            'email_verified_at' => now(),                     // Set the email verification timestamp to the current time.
            'email_verification_code' => null,                // No email verification code initially.
            'email_verification_expires_at' => null,          // No expiration timestamp for email verification initially.
            'password' => static::$password ??= Hash::make('password'), // Hash the default password if not already hashed.
            'remember_token' => Str::random(10),              // Generate a random "remember me" token.
            'last_login_at' => $this->faker->dateTimeThisYear(), // Generate a random last login time within this year.
        ];
    }

    /**
     * Retrieve the default role ID.
     *
     * Uses an existing role if available; otherwise creates new roles using the external sequence.
     *
     * @return int
     */
    protected function getRoleId(): int
    {
        // Attempt to pick an existing status at random from the database.
        $role = Role::query()->inRandomOrder()->first();
        // If no status exists, create new Roles using the Status factory with an external sequence.
        if (!$role) {
            Role::factory()
                ->count(RoleFactory::nOfRolesToBeCreated()) // Create exactly 4 Roles  
                ->state(RoleFactory::allowedSequence()) // Apply external sequence to assign unique names.
                ->create();
            $role = Role::query()->inRandomOrder()->first();
        }
        return $role->id;
    }
    
    /**
     * Retrieve the default status ID.
     *
     * Uses an existing status if available; otherwise creates new statuses using the external sequence.
     *
     * @return int
     */
    protected function getStatusId(): int
    {
        // Attempt to pick an existing status at random from the database.
        $status = Status::query()->inRandomOrder()->first();
        // If no status exists, create new statuses using the Status factory with an external sequence.
        if (!$status) {
            Status::factory()
                ->count(StatusFactory::nOfStatusesToBeCreated()) // Create exactly 3 statuses (e.g., active, inactive, banned).
                ->state(StatusFactory::allowedSequence()) // Apply external sequence to assign unique names.
                ->create();
            $status = Status::query()->inRandomOrder()->first();
        }
        return $status->id;
    }

    /**
     * State to mark a user as an admin.
     *
     * 
     * This state finds (or creates) an admin role and sets it for the user.
     *
     * @return static
     */
    public function admin(): static
    {
        return $this->state(function (array $attributes) {
            // Look for an existing role with the name 'admin'.
            $adminRole = Role::query()->where('name', 'admin')->first();
            // If an admin role doesn't exist, create one using the Role factory.
            if (!$adminRole) {
                Role::factory()
                    ->count(RoleFactory::nOfRolesToBeCreated()) // Create exactly 4 roles.
                    ->state(RoleFactory::allowedSequence()) // Apply external sequence to assign unique names.
                    ->create();
                $adminRole = Role::query()->where('name', 'admin')->first();
            }
            // Return the state modification to set the user's role_id to the admin role's ID.
            return [
                'role_id' => $adminRole->id,
            ];
        });
    }

    /**
     * State to mark a user as inactive.
     *
     * This state finds (or creates) an inactive status and sets it for the user.
     *
     * @return static
     */
    public function inactive(): static
    {
        return $this->state(function (array $attributes) {
            // Look for an existing status with the name 'inactive'.
            $inactiveStatus = Status::query()->where('name', 'inactive')->first();
            // If an inactive status doesn't exist, create one using the Status factory.
            if (!$inactiveStatus) {
                Status::factory()
                    ->count(StatusFactory::nOfStatusesToBeCreated()) // Create exactly 3 statuses (e.g., active, inactive, banned).
                    ->state(StatusFactory::allowedSequence()) // Apply external sequence to assign unique names.
                    ->create();
                $inactiveStatus = Status::query()->where('name', 'inactive')->first();
            }
            // Return the state modification to set the user's status_id to the inactive status's ID.
            return [
                'status_id' => $inactiveStatus->id,
            ];
        });
    }

    /**
     * Indicate that the user's email address should be unverified.
     *
     * @return static
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null, // Set email_verified_at to null to mark the email as unverified.
        ]);
    }

    /**
     * Indicate that the user's email address should be verified.
     *
     * @return static
     */
    public function verified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => now(), // Set email_verified_at to the current time to mark the email as verified.
        ]);
    }

    /**
     * Attach phone records to the user.
     *
     * @param int $count The number of phone records to attach.
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    // public function withPhones(int $count = 2): Factory
    // {
    //     // Create and associate $count Phone model instances with the User.
    //     return $this->has(Phone::factory()->count($count));
    // }

    /**
     * Attach a profile to the user.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    // public function withProfile(): Factory
    // {
    //     // Create and associate a Profile model instance with the User.
    //     return $this->has(Profile::factory());
    // }
}