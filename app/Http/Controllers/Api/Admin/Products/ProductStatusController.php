<?php

namespace App\Http\Controllers\Api\Admin\Products;

use Exception;
use RuntimeException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;


class ProductStatusController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|unique:product_statuses,name|max:256',
            ]);

            if ($validator->fails()) {
                Log::warning("Product status storing validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }

            $validatedData = $validator->validated();

            $statusID = DB::table('product_statuses')->insertGetId([
                'name' => $validatedData['name']
            ]);

            $status = DB::table('product_statuses')->where('id', $statusID)->first();

            return ApiResponse::success($status, 'Status created successfully.');
        } catch (Exception $e) {
            Log::error("Error creating status: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|unique:product_statuses,name|max:256',
            ]);

            if ($validator->fails()) {
                Log::warning("Product status storing validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }

            $validatedData = $validator->validated();

            $updates = DB::table('product_statuses')->where('id', $id)->update([
                'name' => $validatedData['name']
            ]);

            if (!($updates > 0)) {
                throw new RuntimeException('Error updating status');
            }

            $status = DB::table('product_statuses')->where('id', $id)->first();
            return ApiResponse::success($status, 'Status updated successfully.');
        } catch (Exception $e) {
            Log::error("Error updating status: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function delete(string $id)
    {
        try {
            $deletes = DB::table('product_statuses')->delete($id);

            if (!($deletes > 0)) {
                throw new RuntimeException('Error deleting status');
            }
            
            return ApiResponse::success([], 'Status deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting status: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}