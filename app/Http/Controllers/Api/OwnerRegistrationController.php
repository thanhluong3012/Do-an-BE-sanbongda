<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OwnerRegistration;
use App\Models\User;
use App\Models\Role;
use App\Models\OwnerProfile;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;

class OwnerRegistrationController extends Controller
{
    /**
     * Display a listing of the resource.
     * Admin xem danh sách yêu cầu đăng ký chủ sân
     */
    public function index(Request $request)
    {
        try {
            $query = OwnerRegistration::with('approver');

            // Filter by status nếu có
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $registrations = $query->orderBy('created_at', 'desc')->get();

            return ApiResponse::success('Danh sách yêu cầu đăng ký', $registrations, 200);
        } catch (Exception $e) {
            return ApiResponse::error('Lấy danh sách thất bại', $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * User đăng ký làm chủ sân (Public - không cần đăng nhập)
     */
    public function store(Request $request)
    {
        try {
            // Validate
            $validator = Validator::make($request->all(), [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:owner_registrations,email', 'unique:users,email'],
                'phone' => ['required', 'string', 'max:20'],
                'password' => ['required', 'string', 'min:6'],
                'image_code' => ['nullable', 'string'],
                'code_id' => ['nullable', 'string', 'max:20'],
                'business_name' => ['nullable', 'string', 'max:255'],
                'address' => ['nullable', 'string'],
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            // Tạo yêu cầu đăng ký
            $registration = OwnerRegistration::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password), // Hash password ngay
                'image_code' => $request->image_code,
                'code_id' => $request->code_id,
                'business_name' => $request->business_name,
                'address' => $request->address,
                'status' => 'pending',
            ]);

            return ApiResponse::success(
                'Yêu cầu đăng ký đã được gửi. Vui lòng chờ admin duyệt.',
                $registration,
                201
            );
        } catch (Exception $e) {
            return ApiResponse::error('Đăng ký thất bại', $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     * Xem chi tiết yêu cầu
     */
    public function show(string $id)
    {
        try {
            $registration = OwnerRegistration::with('approver')->find($id);

            if (!$registration) {
                return ApiResponse::error('Không tìm thấy yêu cầu', null, 404);
            }

            return ApiResponse::success('Chi tiết yêu cầu đăng ký', $registration, 200);
        } catch (Exception $e) {
            return ApiResponse::error('Lấy thông tin thất bại', $e->getMessage(), 500);
        }
    }

    /**
     * Admin DUYỆT yêu cầu đăng ký
     * Tạo User + OwnerProfile
     */
    public function approve(string $id)
    {
        DB::beginTransaction();
        try {
            $registration = OwnerRegistration::find($id);

            if (!$registration) {
                return ApiResponse::error('Không tìm thấy yêu cầu', null, 404);
            }

            if ($registration->status !== 'pending') {
                return ApiResponse::error('Yêu cầu đã được xử lý trước đó', null, 400);
            }

            // Tìm role owner
            $ownerRole = Role::where('name_role', 'owner')->first();
            if (!$ownerRole) {
                DB::rollBack();
                return ApiResponse::error('Role owner không tồn tại', null, 500);
            }

            // Tạo User
            $user = User::create([
                'role_id' => $ownerRole->id,
                'name' => $registration->name,
                'email' => $registration->email,
                'phone' => $registration->phone,
                'password' => $registration->password, // Đã hash rồi
                'status' => true,
            ]);

            // Tạo OwnerProfile
            $ownerProfile = OwnerProfile::create([
                'user_id' => $user->id,
                'image_code' => $registration->image_code,
                'code_id' => $registration->code_id,
            ]);

            // Cập nhật registration
            $registration->update([
                'status' => 'approved',
                'approved_by' => auth('api')->id(),
                'approved_at' => now(),
            ]);

            DB::commit();

            // TODO: Gửi email thông báo "Tài khoản đã được duyệt"

            return ApiResponse::success(
                'Đã duyệt yêu cầu thành công',
                [
                    'user' => $user,
                    'owner_profile' => $ownerProfile,
                ],
                200
            );
        } catch (Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Duyệt yêu cầu thất bại', $e->getMessage(), 500);
        }
    }

    /**
     * Admin TỪ CHỐI yêu cầu đăng ký
     */
    public function reject(Request $request, string $id)
    {
        try {
            $registration = OwnerRegistration::find($id);

            if (!$registration) {
                return ApiResponse::error('Không tìm thấy yêu cầu', null, 404);
            }

            if ($registration->status !== 'pending') {
                return ApiResponse::error('Yêu cầu đã được xử lý trước đó', null, 400);
            }

            // Validate lý do từ chối
            $validator = Validator::make($request->all(), [
                'rejected_reason' => ['required', 'string'],
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Vui lòng nhập lý do từ chối', $validator->errors(), 422);
            }

            // Cập nhật status
            $registration->update([
                'status' => 'rejected',
                'rejected_reason' => $request->rejected_reason,
                'approved_by' => auth('api')->id(),
                'approved_at' => now(),
            ]);

            // TODO: Gửi email thông báo "Yêu cầu bị từ chối vì: ..."

            return ApiResponse::success('Đã từ chối yêu cầu', $registration, 200);
        } catch (Exception $e) {
            return ApiResponse::error('Từ chối yêu cầu thất bại', $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * Xóa yêu cầu (nếu cần)
     */
    public function destroy(string $id)
    {
        try {
            $registration = OwnerRegistration::find($id);

            if (!$registration) {
                return ApiResponse::error('Không tìm thấy yêu cầu', null, 404);
            }

            $registration->delete();

            return ApiResponse::success('Xóa yêu cầu thành công', null, 200);
        } catch (Exception $e) {
            return ApiResponse::error('Xóa thất bại', $e->getMessage(), 500);
        }
    }
}
