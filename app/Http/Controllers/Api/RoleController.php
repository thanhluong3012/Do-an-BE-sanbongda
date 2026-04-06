<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Helpers\ApiResponse;
use Exception;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $roles = Role::all();
            return ApiResponse::success('Roles retrieved successfully',
                $roles,
                200
            );
        } catch (Exception $e) {
            return ApiResponse::error('Failed to retrieve roles',
                $e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Validate
            $validator = Validator::make($request->all(), [
                'name' => ['required', 'string', 'max:255', 'unique:roles,name_role'],
                'description' => ['nullable', 'string', 'max:500'],
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            // Create
            $role = Role::create([
                'name_role' => $request->string('name'),
                'description' => $request->string('description'),
            ]);

            return ApiResponse::success('Created role successfully', $role, 201);
        } catch (Exception $e) {
            return ApiResponse::error('Failed to create role',
                $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $role = Role::find($id);
        if (!$role) {
            return ApiResponse::error('Không tìm thấy role', null, 404);
        }
        return ApiResponse::success('Lấy role thành công', $role, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $role = Role::find($id);

            if (!$role) {
                return ApiResponse::error('Không tìm thấy role', null, 404);
            }

            // Validate (update thì cho phép "sometimes")
            $validator = Validator::make($request->all(), [
                'name' => ['sometimes', 'string', 'max:255', 'unique:roles,name_role,' . $role->id],
                'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            // Update fields nếu có gửi lên
            if ($request->filled('name')) $role->name_role = $request->string('name');
            if ($request->has('description')) $role->description = $request->string('description');

            $role->save();

            return ApiResponse::success('Cập nhật role thành công', $role, 200);
        } catch (Exception $e) {
            return ApiResponse::error('Cập nhật role thất bại',
                $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $role = Role::find($id);
        if (!$role) {
            return ApiResponse::error('Không tìm thấy role', null, 404);
        }
        $role->delete();
        return ApiResponse::success('Xóa role thành công', null, 200);
    }
}
