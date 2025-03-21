<?php 

namespace App\Repositories\Eloquent;

use App\Repositories\Eloquent\BaseRepository;
use App\Models\Role;

class RoleRepository extends BaseRepository
{
    public function __construct(Role $role)
    {
        parent::__construct($role);
    }
    // add custom methods specific to Role here
    /**
     * Find a role by name.
     */
    public function findByName(string $name): ?Role
    {
        return $this->model->where('name', $name)->first();
    }
}