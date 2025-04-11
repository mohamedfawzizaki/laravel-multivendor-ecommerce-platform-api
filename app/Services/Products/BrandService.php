<?php

namespace App\Services\Products;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Repos\Products\BrandRepository;

class BrandService
{
    /**
     * Constructor to inject the BrandRepository dependency.
     *
     * @param \App\Repositories\Repos\Products\BrandRepository $brandRepository
     */
    public function __construct(private BrandRepository $brandRepository) {}

    public function getAllBrands(
        ?int $perPage = null,
        array $columns = ['*'],
        ?string $pageName = null,
        ?int $page = null,
        bool $withTrashed = false,
        bool $onlyTrashed = false,
        array $conditions = []
    ): Collection|LengthAwarePaginator {
        return $this->brandRepository->getAllUsingRepositoryBaseTrait(
            $perPage,
            $columns,
            $pageName,
            $page,
            $withTrashed,
            $onlyTrashed,
            $conditions
        );
    }

    public function getBrandById(string $id, array $columns = ['*']): ?object
    {
        return $this->brandRepository->getByIdUsingRepositoryBaseTrait($id, $columns);
    }

    public function searchBy(string $field, mixed $value, array $columns = ['*']): ?object
    {
        return $this->brandRepository->searchByUsingRepositoryBaseTrait($field, $value, $columns);
    }

    public function create(array $data): ?object
    {
        return $this->brandRepository->createUsingRepositoryBaseTrait($data);
    }

    public function update(string $id, array $data, array $columns = ['*']): ?object
    {
        return $this->brandRepository->updateUsingRepositoryBaseTrait($id, $data, $columns);
    }

    public function updateGroup(array $data, array $conditions = [], array $columns = ['*']): Collection
    {
        return $this->brandRepository->updateGroupUsingRepositoryBaseTrait($data, $conditions, $columns);
    }

    public function delete(string $id, bool $force = false)
    {
        return $this->brandRepository->deleteUsingRepositoryBaseTrait($id, $force);
    }

    public function deleteBulk(array $conditions, bool $force = false)
    {
        return $this->brandRepository->deleteBulkUsingRepositoryBaseTrait($conditions, $force);
    }

    public function softDeleted(string $id)
    {
        return $this->brandRepository->softDeletedUsingRepositoryBaseTrait($id);
    }

    public function restore(string $id, array $columns = ['*'])
    {
        return $this->brandRepository->restoreUsingRepositoryBaseTrait($id, $columns);
    }

    public function restoreBulk(array $conditions = [], array $columns = ['*'])
    {
        return $this->brandRepository->restoreBulkUsingRepositoryBaseTrait($conditions, $columns);
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