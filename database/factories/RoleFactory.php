<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

/**
 * This factory is responsible for generating Role model instances.
 *
 * Allowed role names: 'admin', 'customer', 'seller', 'employee'.
 * To ensure that each role name is used only once when creating multiple roles,
 * you should apply the provided sequence via the `allowedSequence()` method.
 *
 * For example:
 * 
 *     Role::factory()
 *         ->count(4)
 *         ->state(RoleFactory::allowedSequence())
 *         ->create();
 *
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * The model that this factory creates.
     *
     * @var class-string<Role>
     */
    protected $model = Role::class; // Specify that this factory creates Role instances.

    // do function to dynamically get the array count?
    // public static int $nOfRolesToBeCreated = 4;
    public static array $allowedRoledNames = [  ['name' => 'admin'],
                                                ['name' => 'customer'],
                                                ['name' => 'seller'],
                                                ['name' => 'employee'],
                                                ['name' => 'guest'],
                                                ['name' => 'moderator'],
                                                ['name' => 'superadmin'],
                                                ['name' => 'user'],
                                            ];
    /**
     * Define the model's default state.
     *
     * Note: The 'name' field is set to a random allowed value by default.
     * When creating multiple roles, you should apply a sequence externally
     * to ensure that the allowed names are unique.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Set 'name' to a random element from the allowed list.
            // When creating a single role, this may suffice.
            'name' => $this->faker->randomElement(['admin', 'customer', 'seller', 'employee']),
            
            // Generate a random sentence for the role's description.
            'description' => $this->faker->sentence(),
            
            // Generate random boolean values for each permission.
            // These will be stored as JSON in the database.
            // 'permissions' => [
            //     'can_create' => $this->faker->boolean, // Random true/false for create permission.
            //     'can_edit'   => $this->faker->boolean, // Random true/false for edit permission.
            //     'can_update' => $this->faker->boolean, // Random true/false for update permission.
            //     'can_delete' => $this->faker->boolean, // Random true/false for delete permission.
            //     'can_read'   => $this->faker->boolean, // Random true/false for read permission.
            // ],
        ];
    }

    /**
     * Returns a Sequence for the allowed role names.
     *
     * Use this sequence when creating multiple roles to assign each one
     * of the allowed names ('admin', 'customer', 'seller', 'employee') uniquely.
     *
     * @return Sequence
     */
    public static function allowedSequence(): Sequence
    {
        // Define a Sequence that cycles through the allowed role names.
        return new Sequence(...static::$allowedRoledNames);
    }
    public static function nOfRolesToBeCreated(): int
    {
        return count(static::$allowedRoledNames);
    }
    /**
     * State to create an admin role.
     *
     * This state forces the role name to 'admin' and sets all permissions to true.
     *
     * @return static
     */
    public function admin(): static
    {
        return $this->state(function (array $attributes) {
            return [
                // Force the 'name' to be 'admin'.
                'name' => 'admin',
                
                // Set all permissions to true for the admin role.
                // 'permissions' => [
                //     'can_create' => true,  // Admin can create.
                //     'can_edit'   => true,  // Admin can edit.
                //     'can_update' => true,  // Admin can update.
                //     'can_delete' => true,  // Admin can delete.
                //     'can_read'   => true,  // Admin can read.
                // ],
            ];
        });
    }
    public function withUsers(int $count = 2): Factory
    {
        // Create and associate $count User model instances with the User.
        return $this->has(User::factory()->count($count));
    }
    
}