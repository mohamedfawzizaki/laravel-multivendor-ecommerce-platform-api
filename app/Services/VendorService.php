<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Repos\VendorRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class VendorService
{
    /**
     * Constructor to inject the VendorRepository dependency.
     *
     * @param VendorRepository $vendorRepository The repository responsible for vendor database operations.
     */
    public function __construct(private VendorRepository $vendorRepository) {}

    public function getAllVendors(
        ?int $perPage = null,
        array $columns = ['*'],
        ?string $pageName = null,
        ?int $page = null,
        bool $withTrashed = false,
        bool $onlyTrashed = false,
        array $conditions = []
    ): Collection|LengthAwarePaginator {
        return $this->vendorRepository->getAllUsingRepositoryBaseTrait(
            $perPage,
            $columns,
            $pageName,
            $page,
            $withTrashed,
            $onlyTrashed,
            $conditions
        );
    }

    public function getVendorById(string $id, array $columns = ['*']): ?object
    {
        return $this->vendorRepository->getByIdUsingRepositoryBaseTrait($id, $columns);
    }

    public function searchBy(string $field, mixed $value, array $columns = ['*']): ?object
    {
        return $this->vendorRepository->searchByUsingRepositoryBaseTrait($field, $value, $columns);
    }

    public function create(array $data): ?object
    {
        return $this->vendorRepository->createUsingRepositoryBaseTrait($data);
    }

    public function update(string $id, array $data, array $columns = ['*']): ?object
    {
        return $this->vendorRepository->updateUsingRepositoryBaseTrait($id, $data, $columns);
    }

    public function updateGroup(array $data, array $conditions = [], array $columns = ['*']): Collection
    {
        return $this->vendorRepository->updateGroupUsingRepositoryBaseTrait($data, $conditions, $columns);
    }

    public function delete(string $id, bool $force = false)
    {
        return $this->vendorRepository->deleteUsingRepositoryBaseTrait($id, $force);
    }

    public function deleteBulk(array $conditions, bool $force = false)
    {
        return $this->vendorRepository->deleteBulkUsingRepositoryBaseTrait($conditions, $force);
    }

    public function softDeleted(string $id)
    {
        return $this->vendorRepository->softDeletedUsingRepositoryBaseTrait($id);
    }

    public function restore(string $id, array $columns = ['*'])
    {
        return $this->vendorRepository->restoreUsingRepositoryBaseTrait($id, $columns);
    }

    public function restoreBulk(array $conditions = [], array $columns = ['*'])
    {
        return $this->vendorRepository->restoreBulkUsingRepositoryBaseTrait($conditions, $columns);
    }

    public function assignAddressByCityName(string $cityName, string $vendorID): bool
    {
        $city = DB::table('cities')->where('name', $cityName)->first();

        $result = DB::table('vendor_shop_address')->insert(
            [
                'vendor_shop_id' => $vendorID,
                'city_id' => $city->id,
                'created_at' => Carbon::now(),
            ]
        );

        return $result;
    }

    public function assignAddressByCityID(string $cityID, string $vendorID): bool
    {
        $city = DB::table('cities')->where('id', $cityID)->first();

        $result = DB::table('vendor_shop_address')->insert(
            [
                'vendor_shop_id' => $vendorID,
                'city_id' => $city->id,
                'created_at' => Carbon::now(),
            ]
        );

        return $result;
    }

    public function storeFile($file, $path)
    {
        $storedPath = $file->store($path, 'public');
        return Storage::url($storedPath);
    }

    public function getPath($url, $path)
    {
        // Extract file path from URL
        $relativePath = str_replace(Storage::url($path), '', $url);

        // Full path in storage
        $fullPath = $path . '/' . ltrim($relativePath, '/');

        // Check if the file exists before deleting
        if (Storage::disk('public')->exists($fullPath)) {
            return $fullPath;
        }

        return null;
    }

    public function deleteFile($path)
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
            return true;
        }

        return false;
    }
}