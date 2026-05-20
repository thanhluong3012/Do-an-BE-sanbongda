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
                'qr_code_image' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'url',
                    'max:1000',
                    'regex:/^https?:\/\//'  // Chỉ chấp nhận full URL
                ],
                // 🆕 BANK INFO - Thêm validation cho thông tin tài khoản ngân hàng
                'bank_id' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'size:6',  // BIN code có 6 số
                    'regex:/^[0-9]{6}$/'  // Chỉ chấp nhận số
                ],
                'account_no' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'min:6',
                    'max:20',
                    'regex:/^[0-9]+$/'  // Chỉ chấp nhận số
                ],
                'account_name' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'max:255',
                    'regex:/^[A-Z\s]+$/'  // Chỉ CHỮ HOA và khoảng trắng
                ],
            ], [
                'bank_id.size' => 'Mã ngân hàng phải có đúng 6 số',
                'bank_id.regex' => 'Mã ngân hàng chỉ được chứa số',
                'account_no.regex' => 'Số tài khoản chỉ được chứa số',
                'account_name.regex' => 'Tên chủ tài khoản phải viết HOA không dấu (VD: NGUYEN VAN A)',
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

            // Xử lý QR code image (chỉ chấp nhận full URL)
            if ($request->has('qr_code_image')) {
                if ($request->qr_code_image === null) {
                    // Cho phép xóa QR code
                    $ownerProfile->qr_code_image = null;
                } else {
                    // Validate là full URL
                    if (!filter_var($request->qr_code_image, FILTER_VALIDATE_URL) ||
                        !preg_match('/^https?:\/\//i', $request->qr_code_image)) {
                        return ApiResponse::error('QR code URL phải là đường dẫn đầy đủ (http:// hoặc https://)', null, 422);
                    }
                    $ownerProfile->qr_code_image = $request->qr_code_image;
                }
            }

            // 🆕 Cập nhật bank info
            if ($request->has('bank_id')) {
                $ownerProfile->bank_id = $request->bank_id;
            }
            if ($request->has('account_no')) {
                $ownerProfile->account_no = $request->account_no;
            }
            if ($request->has('account_name')) {
                // Tự động convert sang chữ hoa và trim
                $ownerProfile->account_name = $request->account_name ?
                    strtoupper(trim($request->account_name)) : null;
            }

            $ownerProfile->save();

            // 🎯 Thông báo thêm nếu owner đã cập nhật đủ bank info
            $message = 'Cập nhật thông tin chủ sân thành công';
            if ($ownerProfile->hasBankInfo()) {
                $message .= '. Hệ thống sẽ tự động tạo VietQR cho các booking của bạn.';
            }

            return ApiResponse::success(
                $message,
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
    /**
     * Upload QR Code URL (Frontend upload to Supabase, then send URL here)
     * Frontend upload ảnh lên Supabase, trả về URL, backend chỉ lưu URL vào database
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

            // Validate - CHỈ chấp nhận full URL (không chấp nhận relative path)
            $validator = Validator::make($request->all(), [
                'qr_code_url' => [
                    'required',
                    'string',
                    'url',
                    'max:1000',
                    'regex:/^https?:\/\//'  // Bắt buộc phải bắt đầu bằng http:// hoặc https://
                ],
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            // Đảm bảo không phải relative path
            $qrUrl = $request->qr_code_url;
            if (!filter_var($qrUrl, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $qrUrl)) {
                return ApiResponse::error('QR code URL phải là đường dẫn đầy đủ (http:// hoặc https://)', null, 422);
            }

            // Lưu URL vào database
            $currentUser->ownerProfile->qr_code_image = $qrUrl;
            $currentUser->ownerProfile->save();

            return ApiResponse::success(
                'Lưu QR code thành công',
                [
                    'qr_code_url' => $currentUser->ownerProfile->qr_code_image,
                    'owner_profile' => $currentUser->ownerProfile
                ],
                200
            );
        } catch (Exception $e) {
            return ApiResponse::error('Lưu QR code thất bại', $e->getMessage(), 500);
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

            // Trả về URL trực tiếp từ database
            $qrCodeUrl = $currentUser->ownerProfile->qr_code_image;

            return ApiResponse::success(
                'Thông tin QR code',
                [
                    'qr_code_url' => $qrCodeUrl,
                    'has_qr_code' => !is_null($qrCodeUrl)
                ],
                200
            );
        } catch (Exception $e) {
            return ApiResponse::error('Lấy thông tin thất bại', $e->getMessage(), 500);
        }
    }
}
