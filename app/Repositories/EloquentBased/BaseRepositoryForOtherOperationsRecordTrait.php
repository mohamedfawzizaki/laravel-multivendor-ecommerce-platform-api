<?php

namespace App\Repositories\EloquentBased;

use Exception;
use InvalidArgumentException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait BaseRepositoryForOtherOperationsRecordTrait
{
    public function count(array $conditions = []): int
    {
        return 1;
    }
}