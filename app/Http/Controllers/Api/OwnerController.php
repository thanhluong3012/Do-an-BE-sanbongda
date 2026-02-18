<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OwnerProfile;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class OwnerController extends Controller
{
    /**
     * Display a listing of the resource.
     * Lấy danh sách tất cả owner profiles
     */
    public function index()
    {
        try {
            $owners = OwnerProfile::with(['user', 'staffs'])->get();
            return ApiResponse::success('Danh sách chủ sân', $owners, 200);
        } catch (Exception $e) {
            return ApiResponse::error('Lấy danh sách thất bại', $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * Tạo owner profile mới
     */
    public function store(Request $request)
    {
        try {
            // Validate
            $validator = Validator::make($request->all(), [
                'user_id' => ['required', 'integer', 'exists:users,id', 'unique:owner_profiles,user_id'],
                'image_code' => ['nullable', 'string'],
                'code_id' => ['nullable', 'string', 'max:20'],
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            // Tìm role "owner"
            $ownerRole = \App\Models\Role::where('name_role', 'owner')->first();
            if (!$ownerRole) {
                return ApiResponse::error('Role owner không tồn tại', null, 500);
            }

            // Cập nhật role của user thành "owner"
            $user = \App\Models\User::find($request->user_id);
            $user->role_id = $ownerRole->id;
            $user->save();

            // Tạo owner profile
            $owner = OwnerProfile::create([
                'user_id' => $request->user_id,
                'image_code' => $request->image_code,
                'code_id' => $request->code_id,
            ]);

            return ApiResponse::success('Tạo profile chủ sân thành công', $owner->load('user'), 201);
        } catch (Exception $e) {
            return ApiResponse::error('Tạo profile thất bại', $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     * Xem chi tiết owner profile
     */
    public function show(string $id)
    {
        try {
            $owner = OwnerProfile::with(['user', 'staffs.user'])->find($id);

            if (!$owner) {
                return ApiResponse::error('Không tìm thấy profile', null, 404);
            }

            return ApiResponse::success('Chi tiết chủ sân', $owner, 200);
        } catch (Exception $e) {
            return ApiResponse::error('Lấy thông tin thất bại', $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * Cập nhật owner profile
     */
    public function update(Request $request, string $id)
    {
        try {
            $owner = OwnerProfile::find($id);

            if (!$owner) {
                return ApiResponse::error('Không tìm thấy profile', null, 404);
            }

            // Validate
            $validator = Validator::make($request->all(), [
                'user_id' => ['sometimes', 'integer', 'exists:users,id', 'unique:owner_profiles,user_id,' . $owner->id],
                'image_code' => ['nullable', 'string'],
                'code_id' => ['nullable', 'string', 'max:20'],
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            // Update
            if ($request->filled('user_id')) $owner->user_id = $request->user_id;
            if ($request->has('image_code')) $owner->image_code = $request->image_code;
            if ($request->has('code_id')) $owner->code_id = $request->code_id;

            $owner->save();

            return ApiResponse::success('Cập nhật thành công', $owner, 200);
        } catch (Exception $e) {
            return ApiResponse::error('Cập nhật thất bại', $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * Xóa owner profile
     */
    public function destroy(string $id)
    {
        try {
            $owner = OwnerProfile::find($id);

            if (!$owner) {
                return ApiResponse::error('Không tìm thấy profile', null, 404);
            }

            $owner->delete();

            return ApiResponse::success('Xóa thành công', null, 200);
        } catch (Exception $e) {
            return ApiResponse::error('Xóa thất bại', $e->getMessage(), 500);
        }
    }
}
