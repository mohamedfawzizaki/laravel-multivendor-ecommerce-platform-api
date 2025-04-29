<?php

namespace App\Http\Controllers\Api\Admin\Products;

use Illuminate\Http\Request;
use App\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Products\CategoryHierarchy;


class CategoryHierarchyController extends Controller
{
    public function index()
    {
        return ApiResponse::success(CategoryHierarchy::all(), 'Category hierarchy retrieved.');
    }

    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'parent_id' => 'required|exists:categories,id',
            'child_id' => 'required|exists:categories,id|different:parent_id',
        ])->validate();

        $exists = CategoryHierarchy::where($data)->exists();
        if ($exists) {
            return ApiResponse::error('This relationship already exists.', 409);
        }

        $relationship = CategoryHierarchy::create($data);
        return ApiResponse::success($relationship, 'Hierarchy relationship created.');
    }

    public function destroy($id)
    {
        $hierarchy = CategoryHierarchy::find($id);
        if (!$hierarchy) return ApiResponse::error('Relationship not found.', 404);

        $hierarchy->delete();
        return ApiResponse::success(null, 'Relationship deleted.');
    }
}