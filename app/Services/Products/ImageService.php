<?php

namespace App\Services\Products;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Repos\Products\ImageRepository;

class ImageService
{
    /**
     * Constructor to inject the ImageRepository dependency.
     *
     * @param \App\Repositories\Repos\Products\ImageRepository $imageRepository
     */
    public function __construct(private ImageRepository $imageRepository) {}

    public function getAllImages(
        ?int $perPage = null,
        array $columns = ['*'],
        ?string $pageName = null,
        ?int $page = null,
        bool $withTrashed = false,
        bool $onlyTrashed = false,
        array $conditions = []
    ): Collection|LengthAwarePaginator {
        return $this->imageRepository->getAllUsingRepositoryBaseTrait(
            $perPage,
            $columns,
            $pageName,
            $page,
            $withTrashed,
            $onlyTrashed,
            $conditions
        );
    }

    public function getImageById(string $id, array $columns = ['*']): ?object
    {
        return $this->imageRepository->getByIdUsingRepositoryBaseTrait($id, $columns);
    }

    public function searchBy(string $field, mixed $value, array $columns = ['*']): ?object
    {
        return $this->imageRepository->searchByUsingRepositoryBaseTrait($field, $value, $columns);
    }

    public function create(array $data): ?object
    {
        return $this->imageRepository->createUsingRepositoryBaseTrait($data);
    }

    public function update(string $id, array $data, array $columns = ['*']): ?object
    {
        return $this->imageRepository->updateUsingRepositoryBaseTrait($id, $data, $columns);
    }

    public function delete(string $id, bool $force = false)
    {
        return $this->imageRepository->deleteUsingRepositoryBaseTrait($id, $force);
    }

    public function deleteBulk(array $conditions, bool $force = false)
    {
        return $this->imageRepository->deleteBulkUsingRepositoryBaseTrait($conditions, $force);
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