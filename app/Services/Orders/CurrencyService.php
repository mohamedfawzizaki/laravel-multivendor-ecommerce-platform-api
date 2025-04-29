<?php

namespace App\Services\Orders;

use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Repos\Orders\CurrencyRepository;

class CurrencyService
{
    /**
     * Constructor to inject the CurrencyRepository dependency.
     *
     * @param \App\Repositories\Repos\Orders\CurrencyRepository $currencyRepository
     */
    public function __construct(private CurrencyRepository $currencyRepository) {}

    public function getAllCurrencies(
        ?int $perPage = null,
        array $columns = ['*'],
        ?string $pageName = null,
        ?int $page = null,
        bool $withTrashed = false,
        bool $onlyTrashed = false,
        array $conditions = []
    ): Collection|LengthAwarePaginator {
        return $this->currencyRepository->getAllUsingRepositoryBaseTrait(
            $perPage,
            $columns,
            $pageName,
            $page,
            $withTrashed,
            $onlyTrashed,
            $conditions
        );
    }


    public function searchBy(string $field, mixed $value, array $columns = ['*']): ?object
    {
        return $this->currencyRepository->searchByUsingRepositoryBaseTrait($field, $value, $columns);
    }

    public function create(array $data): ?object
    {
        return $this->currencyRepository->createUsingRepositoryBaseTrait($data);
    }

    public function update(string $id, array $data, array $columns = ['*']): ?object
    {
        return $this->currencyRepository->updateUsingRepositoryBaseTrait($id, $data, $columns);
    }

    public function delete(string $id, bool $force = false)
    {
        return $this->currencyRepository->deleteUsingRepositoryBaseTrait($id, $force);
    }

    public function getActive()
    {
        return $this->currencyRepository->getActive();
    }
    
    public function getBase()
    {
        return $this->currencyRepository->getBase();
    }
    public function convertTo(string $targetCurrencyCode, float $amount)
    {
        $targetCurrency = $this->currencyRepository->findByField('code', $targetCurrencyCode);

        if (!$targetCurrency) {
            throw new \Exception("Target Currency not found");
        }

        return $this->currencyRepository->convertTo($targetCurrency, $amount);
    }

    public function convert(string $fromCode, string $toCode, float $amount): float
    {
        return $this->currencyRepository->convert($fromCode, $toCode, $amount);
    }
}