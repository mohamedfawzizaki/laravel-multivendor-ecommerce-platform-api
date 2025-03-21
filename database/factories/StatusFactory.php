<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

/**
 * This factory is responsible for generating Status model instances.
 *
 * Allowed status names: 'active', 'inactive', 'banned'.
 * When creating multiple statuses, you can apply the external sequence returned by
 * the allowedSequence() method to ensure that each status gets a unique name.
 *
 * @extends Factory<Status>
 */
class StatusFactory extends Factory
{
    /**
     * The model that this factory creates.
     *
     * @var class-string<Status>
     */
    protected $model = Status::class; // Specify that this factory creates Status instances.

    // do function to dynamically get the array count?
    // public static int $nOfStatusesToBeCreated = 3;
    public static array $allowedStatusNames = [ ['name' => 'active'],
                                                ['name' => 'inactive'],
                                                ['name' => 'banned'],
                                                ['name' => 'pendding'],
                                            ];

    /**
     * Define the model's default state.
     *
     * By default, the 'name' attribute is set to a random allowed value.
     * You can override this by applying an external sequence state.
     *
     * @return array<string, mixed> An array of default attributes for a Status.
     */
    public function definition(): array
    {
        return [
            // Set the 'name' attribute to a random allowed value.
            // If you want to assign the allowed names sequentially and uniquely,
            // you should apply an external sequence using the allowedSequence() method.
            'name' => $this->faker->randomElement(['active', 'inactive', 'banned']),
            
            // Generate a random sentence to serve as the description of the status.
            'description' => $this->faker->sentence(),
            
            // Note: The 'updated_at' column is managed by the database defaults.
        ];
    }

    /**
     * Returns a Sequence for the allowed status names.
     *
     * Use this sequence when creating multiple statuses to ensure that each status
     * receives one of the allowed names ('active', 'inactive', 'banned') uniquely.
     *
     * @return Sequence
     */
    public static function allowedSequence(): Sequence
    {
        return new Sequence(...static::$allowedStatusNames );
    }
    public static function nOfStatusesToBeCreated(): int
    {
        return count(static::$allowedStatusNames);
    }

    public function withUsers(int $count = 2): Factory
    {
        // Create and associate $count User model instances with the User.
        return $this->has(User::factory()->count($count));
    }
}