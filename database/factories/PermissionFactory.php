<?php

namespace Database\Factories;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

/**
 * This factory is responsible for generating Permission model instances.
 *
 * Allowed permission names: 'admin', 'customer', 'seller', 'employee'.
 * To ensure that each permission name is used only once when creating multiple permissions,
 * you should apply the provided sequence via the `allowedSequence()` method.
 *
 * For example:
 * 
 *     Permission::factory()
 *         ->count(4)
 *         ->state(PermissionFactory::allowedSequence())
 *         ->create();
 *
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    /**
     * The model that this factory creates.
     *
     * @var class-string<Permission>
     */
    protected $model = Permission::class; // Specify that this factory creates Permission instances.

    // do function to dynamically get the array count?
    // public static int $nOfPermissionsToBeCreated = 4;
    public static array $allowedPermissiondNames = [
        ['name' => 'view_products', 'description' => 'Allows viewing of products'],
        ['name' => 'place_orders', 'description' => 'Allows placing orders'],
        ['name' => 'track_orders', 'description' => 'Allows tracking of orders'],
    ];
    /**
     * Define the model's default state.
     *
     * Note: The 'name' field is set to a random allowed value by default.
     * When creating multiple permissions, you should apply a sequence externally
     * to ensure that the allowed names are unique.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Set 'name' to a random element from the allowed list.
            // When creating a single permission, this may suffice.
            'name' => $this->faker->randomElement(['admin', 'customer', 'seller', 'employee']),

            // Generate a random sentence for the permission's description.
            'description' => $this->faker->sentence(),
        ];
    }

    /**
     * Returns a Sequence for the allowed permission names.
     *
     * @return Sequence
     */
    public static function allowedSequence(): Sequence
    {
        // Define a Sequence that cycles through the allowed permission names.
        return new Sequence(...static::$allowedPermissiondNames);
    }
    public static function nOfPermissionsToBeCreated(): int
    {
        return count(static::$allowedPermissiondNames);
    }
}