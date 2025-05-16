<?php

namespace App\Http\Controllers\Api\Admin\Orders;

use App\Models\Orders\TaxRule;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\StoreTaxRequest;
use App\Http\Requests\Orders\UpdateTaxRequest;

class TaxController extends Controller
{
    public function index(): JsonResponse
    {
        $taxRules = TaxRule::all();
        return ApiResponse::success($taxRules, 'Tax rules retrieved successfully.');
    }

    public function show(string $id): JsonResponse
    {
        $taxRule = TaxRule::find($id);

        if (!$taxRule) {
            return ApiResponse::error('Tax rule not found.', 404);
        }

        return ApiResponse::success($taxRule, 'Tax rule retrieved successfully.');
    }

    public function store(StoreTaxRequest $request): JsonResponse
    {
        $taxRule = TaxRule::create($request->validated());

        return ApiResponse::success($taxRule, 'Tax rule created successfully.', 201);
    }

    public function update(string $id, UpdateTaxRequest $request): JsonResponse
    {
        $taxRule = TaxRule::find($id);

        if (!$taxRule) {
            return ApiResponse::error('Tax rule not found.', 404);
        }

        $taxRule->update($request->validated());

        return ApiResponse::success($taxRule, 'Tax rule updated successfully.');
    }

    public function delete(string $id): JsonResponse
    {
        $taxRule = TaxRule::find($id);

        if (!$taxRule) {
            return ApiResponse::error('Tax rule not found.', 404);
        }

        $taxRule->delete();

        return ApiResponse::success(null, 'Tax rule deleted successfully.');
    }
}