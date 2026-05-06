<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OwnerRegistration;
use App\Models\User;
use App\Models\Role;
use App\Models\OwnerProfile;
use App\Mail\OwnerRegistrationApprovedMail;
use App\Mail\OwnerRegistrationRejectedMail;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
                'email' => ['required', 'email:rfc,dns', 'max:255', 'unique:owner_registrations,email', 'unique:users,email'],
                'phone' => ['required', 'string', 'max:20'],
                'password' => ['required', 'string', 'min:6'],
                'image_code' => ['nullable', 'string'],
                'code_id' => ['nullable', 'string', 'max:20'],
                'business_name' => ['nullable', 'string', 'max:255'],
                'address' => ['nullable', 'string'],
            ], [
                'email.email' => 'Email không tồn tại hoặc không hợp lệ. Vui lòng nhập địa chỉ email thật.',
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            // Tạo yêu cầu đăng ký
            $registration = OwnerRegistration::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => $request->password, // Lưu tạm plaintext để gửi email khi duyệt
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

        $ownerRole = Role::where('name_role', 'owner')->first();
        if (!$ownerRole) {
            DB::rollBack();
            return ApiResponse::error('Role owner không tồn tại', null, 500);
        }

        $plainPassword = $registration->password; // Lấy plaintext đã lưu lúc đăng ký

        $user = User::create([
            'role_id' => $ownerRole->id,
            'name' => $registration->name,
            'email' => $registration->email,
            'phone' => $registration->phone,
            'password' => Hash::make($plainPassword), // Hash trước khi lưu vào users
            'status' => true,
            'email_verified_at' => now(), // admin duyệt = đã xác nhận
        ]);

        $ownerProfile = OwnerProfile::create([
            'user_id' => $user->id,
            'business_name' => $registration->business_name,
            'address' => $registration->address,
            'image_code' => $registration->image_code,
            'code_id' => $registration->code_id,
        ]);

        $registration->update([
            'status' => 'approved',
            'approved_by' => auth('api')->id(),
            'approved_at' => now(),
        ]);

        DB::commit();

        // GỬI EMAIL SAU KHI COMMIT - không ảnh hưởng transaction
        try {
            Log::info('=== BẮT ĐẦU GỬI EMAIL APPROVE ===');
            Log::info('Email to: ' . $registration->email);
            Log::info('Name: ' . $registration->name);
            Log::info('Business: ' . $registration->business_name);

            Mail::to($registration->email)->send(
                new OwnerRegistrationApprovedMail(
                    ownerName: $registration->name,
                    ownerEmail: $registration->email,
                    ownerPassword: $plainPassword,
                    businessName: $registration->business_name,
                )
            );

            Log::info('✅ EMAIL APPROVE ĐÃ GỬI THÀNH CÔNG');
        } catch (Exception $mailException) {
            // Log lỗi email nhưng vẫn trả về success
            Log::error('❌ FAILED to send approval email: ' . $mailException->getMessage());
            Log::error('Stack trace: ' . $mailException->getTraceAsString());
        }

        return ApiResponse::success(
            'Đã duyệt và gửi email thông báo',
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
    DB::beginTransaction();

    try {
        $registration = OwnerRegistration::find($id);

        if (!$registration) {
            return ApiResponse::error('Không tìm thấy yêu cầu', null, 404);
        }

        if ($registration->status !== 'pending') {
            return ApiResponse::error('Yêu cầu đã được xử lý trước đó', null, 400);
        }

        $validator = Validator::make($request->all(), [
            'rejected_reason' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Vui lòng nhập lý do từ chối', $validator->errors(), 422);
        }

        $registration->update([
            'status'          => 'rejected',
            'rejected_reason' => $request->rejected_reason,
            'approved_by'     => auth('api')->id(),
            'approved_at'     => now(),
        ]);

        DB::commit();

        // GỬI EMAIL SAU KHI COMMIT - không ảnh hưởng transaction
        try {
            Log::info('=== BẮT ĐẦU GỬI EMAIL REJECT ===');
            Log::info('Email to: ' . $registration->email);
            Log::info('Name: ' . $registration->name);
            Log::info('Business: ' . $registration->business_name);
            Log::info('Reason: ' . $request->rejected_reason);

            Mail::to($registration->email)->send(
                new OwnerRegistrationRejectedMail(
                    $registration->name,
                    $registration->business_name,
                    $request->rejected_reason
                )
            );

            Log::info('✅ EMAIL REJECT ĐÃ GỬI THÀNH CÔNG');
        } catch (Exception $mailException) {
            // Log lỗi email nhưng vẫn trả về success
            Log::error('❌ FAILED to send rejection email: ' . $mailException->getMessage());
            Log::error('Stack trace: ' . $mailException->getTraceAsString());
        }

        return ApiResponse::success('Đã từ chối và gửi email thông báo', $registration, 200);
    } catch (Exception $e) {
        DB::rollBack();
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
