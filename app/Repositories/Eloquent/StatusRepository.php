<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Eloquent\BaseRepository;
use App\Models\Status;

class StatusRepository extends BaseRepository
{
    public function __construct(Status $status)
    {
        parent::__construct($status);
    }
    // add custom methods specific to Status here
    /**
     * Find a status by name.
     */
    public function findByName(string $name): ?Status
    {
        return $this->model->where('name', $name)->first();
    }
}