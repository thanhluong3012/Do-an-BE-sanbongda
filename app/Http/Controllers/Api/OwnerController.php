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

    /**
     * Cập nhật thông tin Owner Profile của chính mình
     * Chỉ owner mới được cập nhật profile của chính mình
     */
    public function updateOwnerProfile(Request $request)
    {
        try {
            $currentUser = auth('api')->user();

            if (!$currentUser) {
                return ApiResponse::error('Không xác thực được người dùng', null, 401);
            }

            // Kiểm tra user có phải owner không
            if ($currentUser->role->name_role !== 'owner') {
                return ApiResponse::error('Chỉ chủ sân mới có thể cập nhật thông tin này', null, 403);
            }

            // Kiểm tra owner profile
            if (!$currentUser->ownerProfile) {
                return ApiResponse::error('Bạn chưa có owner profile', null, 400);
            }

            // Validate
            $validator = Validator::make($request->all(), [
                'business_name' => ['sometimes', 'string', 'max:255'],
                'address' => ['sometimes', 'nullable', 'string'],
                'code_id' => ['sometimes', 'nullable', 'string', 'max:20'],
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            // Cập nhật thông tin
            $ownerProfile = $currentUser->ownerProfile;

            if ($request->filled('business_name')) {
                $ownerProfile->business_name = $request->business_name;
            }

            if ($request->has('address')) {
                $ownerProfile->address = $request->address;
            }

            if ($request->has('code_id')) {
                $ownerProfile->code_id = $request->code_id;
            }

            $ownerProfile->save();

            return ApiResponse::success(
                'Cập nhật thông tin chủ sân thành công',
                $ownerProfile->load('user'),
                200
            );

        } catch (Exception $e) {
            return ApiResponse::error('Cập nhật thất bại', $e->getMessage(), 500);
        }
    }

    /**
     * Upload QR Code cho owner
     * Chỉ owner mới được upload QR code của chính mình
     */
    public function uploadQRCode(Request $request)
    {
        try {
            $currentUser = auth('api')->user();

            if (!$currentUser) {
                return ApiResponse::error('Không xác thực được người dùng', null, 401);
            }

            // Kiểm tra user có phải owner không
            if ($currentUser->role->name_role !== 'owner') {
                return ApiResponse::error('Chỉ chủ sân mới có thể upload QR code', null, 403);
            }

            // Kiểm tra owner profile
            if (!$currentUser->ownerProfile) {
                return ApiResponse::error('Bạn chưa có owner profile', null, 400);
            }

            // Validate
            $validator = Validator::make($request->all(), [
                'qr_code' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            // Xóa QR code cũ nếu có
            if ($currentUser->ownerProfile->qr_code_image) {
                $oldPath = public_path('storage/' . $currentUser->ownerProfile->qr_code_image);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            // Upload QR code mới
            $file = $request->file('qr_code');
            $filename = 'owner_' . $currentUser->ownerProfile->id . '_qr_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('owner_qr_codes', $filename, 'public');

            // Update owner profile
            $currentUser->ownerProfile->qr_code_image = $path;
            $currentUser->ownerProfile->save();

            return ApiResponse::success(
                'Upload QR code thành công',
                [
                    'qr_code_url' => asset('storage/' . $path),
                    'owner_profile' => $currentUser->ownerProfile
                ],
                200
            );
        } catch (Exception $e) {
            return ApiResponse::error('Upload thất bại', $e->getMessage(), 500);
        }
    }

    /**
     * Lấy QR Code của owner
     */
    public function getQRCode()
    {
        try {
            $currentUser = auth('api')->user();

            if (!$currentUser) {
                return ApiResponse::error('Không xác thực được người dùng', null, 401);
            }

            if ($currentUser->role->name_role !== 'owner') {
                return ApiResponse::error('Chỉ chủ sân mới có QR code', null, 403);
            }

            if (!$currentUser->ownerProfile) {
                return ApiResponse::error('Bạn chưa có owner profile', null, 400);
            }

            $qrCodeUrl = null;
            if ($currentUser->ownerProfile->qr_code_image) {
                $qrCodeUrl = asset('storage/' . $currentUser->ownerProfile->qr_code_image);
            }

            return ApiResponse::success(
                'Thông tin QR code',
                [
                    'qr_code_url' => $qrCodeUrl,
                    'has_qr_code' => !is_null($currentUser->ownerProfile->qr_code_image)
                ],
                200
            );
        } catch (Exception $e) {
            return ApiResponse::error('Lấy thông tin thất bại', $e->getMessage(), 500);
        }
    }
}
