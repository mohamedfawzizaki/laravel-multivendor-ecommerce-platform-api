<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Repositories\Repos\UserRepository;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class UserService
{
    /**
     * Constructor to inject the UserRepository dependency.
     *
     * @param UserRepository $userRepository The repository responsible for user database operations.
     */
    public function __construct(private UserRepository $userRepository) {}

    public function getAllUsers(
        ?int $perPage = null,
        array $columns = ['*'],
        ?string $pageName = null,
        ?int $page = null,
        bool $withTrashed = false,
        bool $onlyTrashed = false,
        array $conditions = []
    ): Collection|LengthAwarePaginator {
        return $this->userRepository->getAllUsingRepositoryBaseTrait(
            $perPage,
            $columns,
            $pageName,
            $page,
            $withTrashed,
            $onlyTrashed,
            $conditions
        );
    }

    public function getUserById(string $id, array $columns = ['*']): ?object
    {
        return $this->userRepository->getByIdUsingRepositoryBaseTrait($id, $columns);
    }

    public function searchBy(string $field, mixed $value, array $columns = ['*']): ?object
    {
        return $this->userRepository->searchByUsingRepositoryBaseTrait($field, $value, $columns);
    }

    public function create(array $data): ?object
    {
        return $this->userRepository->createUsingRepositoryBaseTrait($data);
    }

    public function update(string $id, array $data, array $columns = ['*']): ?object
    {
        return $this->userRepository->updateUsingRepositoryBaseTrait($id, $data, $columns);
    }

    public function updateGroup(array $data, array $conditions = [], array $columns = ['*']): Collection
    {
        return $this->userRepository->updateGroupUsingRepositoryBaseTrait($data, $conditions, $columns);
    }

    public function delete(string $id, bool $force = false)
    {
        return $this->userRepository->deleteUsingRepositoryBaseTrait($id, $force);
    }

    public function deleteBulk(array $conditions, bool $force = false)
    {
        return $this->userRepository->deleteBulkUsingRepositoryBaseTrait($conditions, $force);
    }

    public function softDeleted(string $id)
    {
        return $this->userRepository->softDeletedUsingRepositoryBaseTrait($id);
    }

    public function restore(string $id, array $columns = ['*'])
    {
        return $this->userRepository->restoreUsingRepositoryBaseTrait($id, $columns);
    }

    public function restoreBulk(array $conditions = [], array $columns = ['*'])
    {
        return $this->userRepository->restoreBulkUsingRepositoryBaseTrait($conditions, $columns);
    }

    public function assignAddressByCityName(string $cityName, string $userID): bool
    {
        $city = DB::table('cities')->where('name', $cityName)->first();

        $result = DB::table('user_address')->insert(
            [
                'user_id' => $userID,
                'city_id' => $city->id,
                'created_at' => Carbon::now(),
            ]
        );

        return $result;
    }

    public function assignAddressByCityID(string $cityID, string $userID): bool
    {
        $city = DB::table('cities')->where('id', $cityID)->first();

        $result = DB::table('user_address')->insert(
            [
                'user_id' => $userID,
                'city_id' => $city->id,
                'created_at' => Carbon::now(),
            ]
        );

        return $result;
    }
}