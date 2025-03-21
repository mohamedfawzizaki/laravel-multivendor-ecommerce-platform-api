<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Database\Factories\PermissionFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PermissionsSeeder extends Seeder
{
    use WithoutModelEvents;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = Permission::factory()
            ->count(PermissionFactory::nOfPermissionsToBeCreated())
            ->state(PermissionFactory::allowedSequence())
            ->make();
        // Iterate over the collection and save each permission
        $permissions->each(function ($permission) {
            $permissionInDB = Permission::where('name', $permission->name)->first();
            if (!$permissionInDB) {
                $permission->save();
            }
        });
    }
}
