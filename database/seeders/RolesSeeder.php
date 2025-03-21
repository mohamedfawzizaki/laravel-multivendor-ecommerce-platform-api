<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Database\Factories\RoleFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RolesSeeder extends Seeder
{
    use WithoutModelEvents;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = Role::factory()
                    ->count(RoleFactory::nOfRolesToBeCreated())
                    ->state(RoleFactory::allowedSequence())
                    ->make();
        // Iterate over the collection and save each role
        $roles->each(function ($role) {
            $roleInDB = Role::where('name', $role->name)->first();
            if (!$roleInDB) {
                $role->save();
            }
        });
    }
}