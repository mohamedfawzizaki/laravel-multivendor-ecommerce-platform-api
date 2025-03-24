<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface RepositoryInterface
{
    public function getAll(array $columns = ['*'], bool $withTrashed = false, bool $onlyTrashed = false, array $conditions = []): Collection;

    public function paginate(int $perPage = 15, array $columns = ['*'], string $pageName = 'page', int $page = 1, bool $withTrashed = false, bool $onlyTrashed = false, array $conditions = []): LengthAwarePaginator;

    public function findById(int|string $id, array $columns = ['*']): ?Model;

    public function findByField(string $field, mixed $value, array $columns = ['*']): ?Model;

    public function create(array $data): Model;

    public function update(int|string $id, array $data, array $columns = ['*']): Model;
    
    public function updateGroup(array $data, array $conditions = [], array $columns = ['*']): Collection;
    
    public function delete(int|string $id, bool $force = false): bool;

    public function deleteBulkRecords(array $conditions, bool $force = false): int;

    public function checkIsSoftdDeleted(int|string $id): bool;

    public function restoreSoftdDeleted(int|string $id, array $columns = ['*']): Model;

    public function restoreBulkRecords(array $conditions = [], array $columns = ['*']): Collection;

    public function count(array $conditions = []): int;

}