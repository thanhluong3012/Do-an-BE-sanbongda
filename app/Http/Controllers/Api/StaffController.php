<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\User;
use App\Models\Role;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;

class StaffController extends Controller
{
    /**
     * Display a listing of the resource.
     * Lấy danh sách tất cả staff
     * - Admin: xem tất cả staff
     * - Owner: chỉ xem staff thuộc về mình
     */
    public function index()
    {
        try {
            $currentUser = auth('api')->user();

            if (!$currentUser) {
                return ApiResponse::error('Không xác thực được người dùng', null, 401);
            }

            // Admin xem tất cả staff
            if ($currentUser->role->name_role === 'admin') {
                $staffs = Staff::with(['user', 'owner.user'])->get();
            }
            // Owner chỉ xem staff của mình
            elseif ($currentUser->role->name_role === 'owner') {
                if (!$currentUser->ownerProfile) {
                    return ApiResponse::error('Bạn chưa có owner profile', null, 400);
                }
                $staffs = Staff::with(['user', 'owner.user'])
                    ->where('owner_id', $currentUser->ownerProfile->id)
                    ->get();
            }
            else {
                return ApiResponse::error('Bạn không có quyền xem danh sách nhân viên', null, 403);
            }

            return ApiResponse::success('Danh sách nhân viên', $staffs, 200);
        } catch (Exception $e) {
            return ApiResponse::error('Lấy danh sách thất bại', $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * Owner tạo nhân viên mới
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            // Kiểm tra user đang login có phải owner không
            $currentUser = auth('api')->user();

            if (!$currentUser || $currentUser->role->name_role !== 'owner') {
                return ApiResponse::error('Chỉ chủ sân mới được tạo nhân viên', null, 403);
            }

            // Kiểm tra owner có profile không
            if (!$currentUser->ownerProfile) {
                return ApiResponse::error('Bạn chưa có owner profile', null, 400);
            }

            // Validate - KHÔNG nhận user_id, owner_id, role_id từ request
            $validator = Validator::make($request->all(), [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'phone' => ['required', 'string', 'max:20'],
                'password' => ['required', 'string', 'min:6'],
                'code_id' => ['nullable', 'string', 'max:20'],
                'avatar' => ['nullable', 'string'],
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            // Tìm role "staff"
            $staffRole = Role::where('name_role', 'staff')->first();
            if (!$staffRole) {
                DB::rollBack();
                return ApiResponse::error('Role staff không tồn tại', null, 500);
            }

            // Tạo User mới với role = staff (Backend tự set)
            $user = User::create([
                'role_id' => $staffRole->id,  // Backend tự động set
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'status' => true,
            ]);

            // Tạo Staff record (owner_id tự động lấy từ owner đang login)
            $staff = Staff::create([
                'user_id' => $user->id,
                'owner_id' => $currentUser->ownerProfile->id,  // Tự động lấy
                'code_id' => $request->code_id,
                'avatar' => $request->avatar,
            ]);

            DB::commit();

            // TODO: Gửi email cho nhân viên với thông tin đăng nhập

            return ApiResponse::success(
                'Tạo nhân viên thành công',
                $staff->load('user'),
                201
            );
        } catch (Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Tạo nhân viên thất bại', $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     * Xem chi tiết staff
     * - Admin: xem bất kỳ staff nào
     * - Owner: chỉ xem staff thuộc về mình
     */
    public function show(string $id)
    {
        try {
            $staff = Staff::with(['user', 'owner.user'])->find($id);

            if (!$staff) {
                return ApiResponse::error('Không tìm thấy nhân viên', null, 404);
            }

            $currentUser = auth('api')->user();

            // Owner chỉ được xem staff của mình
            if ($currentUser->role->name_role === 'owner') {
                if (!$currentUser->ownerProfile || $staff->owner_id !== $currentUser->ownerProfile->id) {
                    return ApiResponse::error('Bạn không có quyền xem nhân viên này', null, 403);
                }
            }

            return ApiResponse::success('Chi tiết nhân viên', $staff, 200);
        } catch (Exception $e) {
            return ApiResponse::error('Lấy thông tin thất bại', $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * Cập nhật staff
     * - Admin: cập nhật bất kỳ staff nào
     * - Owner: chỉ cập nhật staff của mình, KHÔNG được thay đổi owner_id
     */
    public function update(Request $request, string $id)
    {
        try {
            $staff = Staff::find($id);

            if (!$staff) {
                return ApiResponse::error('Không tìm thấy nhân viên', null, 404);
            }

            $currentUser = auth('api')->user();

            // Owner chỉ được cập nhật staff của mình
            if ($currentUser->role->name_role === 'owner') {
                if (!$currentUser->ownerProfile || $staff->owner_id !== $currentUser->ownerProfile->id) {
                    return ApiResponse::error('Bạn không có quyền cập nhật nhân viên này', null, 403);
                }

                // Owner KHÔNG được phép thay đổi owner_id hoặc user_id
                if ($request->has('owner_id') || $request->has('user_id')) {
                    return ApiResponse::error('Bạn không được phép thay đổi owner_id hoặc user_id', null, 403);
                }
            }

            // Validate - chỉ cho phép cập nhật code_id và avatar
            $validator = Validator::make($request->all(), [
                'code_id' => ['nullable', 'string', 'max:20'],
                'avatar' => ['nullable', 'string'],
                // Admin có thể update owner_id và user_id
                'user_id' => ['sometimes', 'integer', 'exists:users,id', 'unique:staffs,user_id,' . $staff->id],
                'owner_id' => ['sometimes', 'integer', 'exists:owner_profiles,id'],
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            // Admin có thể update tất cả
            if ($currentUser->role->name_role === 'admin') {
                if ($request->filled('user_id')) $staff->user_id = $request->user_id;
                if ($request->filled('owner_id')) $staff->owner_id = $request->owner_id;
            }

            // Cả Admin và Owner đều có thể update code_id và avatar
            if ($request->has('code_id')) $staff->code_id = $request->code_id;
            if ($request->has('avatar')) $staff->avatar = $request->avatar;

            $staff->save();

            return ApiResponse::success('Cập nhật thành công', $staff->load('user', 'owner.user'), 200);
        } catch (Exception $e) {
            return ApiResponse::error('Cập nhật thất bại', $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * Xóa staff
     * - Admin: xóa bất kỳ staff nào
     * - Owner: chỉ xóa staff của mình
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {
            $staff = Staff::find($id);

            if (!$staff) {
                return ApiResponse::error('Không tìm thấy nhân viên', null, 404);
            }

            $currentUser = auth('api')->user();

            // Owner chỉ được xóa staff của mình
            if ($currentUser->role->name_role === 'owner') {
                if (!$currentUser->ownerProfile || $staff->owner_id !== $currentUser->ownerProfile->id) {
                    return ApiResponse::error('Bạn không có quyền xóa nhân viên này', null, 403);
                }
            }

            // Lưu user_id trước khi xóa staff để xóa luôn user
            $userId = $staff->user_id;

            // Xóa staff record
            $staff->delete();

            // Xóa luôn user record (cascade)
            // User sẽ tự động bị xóa do foreign key cascade

            DB::commit();

            return ApiResponse::success('Xóa nhân viên thành công', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Xóa thất bại', $e->getMessage(), 500);
        }
    }
}
