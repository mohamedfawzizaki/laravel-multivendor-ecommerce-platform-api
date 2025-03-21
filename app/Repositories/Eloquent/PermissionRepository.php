<?php 

namespace App\Repositories\Eloquent;

use App\Repositories\Eloquent\BaseRepository;
use App\Models\Permission;

class PermissionRepository extends BaseRepository
{
    public function __construct(Permission $permission)
    {
        parent::__construct($permission);
    }
    // add custom methods specific to Permission here
    /**
     * Find a permission by name.
     */
    public function findByName(string $name): ?Permission
    {
        return $this->model->where('name', $name)->first();
    }
}