<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Helpers\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;

class AuthController extends Controller
{
    /**
     * Lấy JWT guard instance với type hint cho IDE
     *
     * @return \Tymon\JWTAuth\JWTGuard
     */
    protected function guard()
    {
        return Auth::guard('api');
    }

    /**
     * ĐĂNG KÝ TÀI KHOẢN MỚI
     *
     * Nhận email, password, name từ FE
     * Validate dữ liệu qua RegisterRequest
     * Hash password và lưu vào database
     * Trả về user info + JWT token
     *
     * @param RegisterRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegisterRequest $request)
    {
        try {
            // Dữ liệu đã được validate tự động qua RegisterRequest
            // Tìm role "member" để gán mặc định cho user mới đăng ký
            $memberRole = \App\Models\Role::where('name_role', 'member')->first();

            if (!$memberRole) {
                return ApiResponse::error(
                    'Registration failed',
                    'Role member không tồn tại trong hệ thống',
                    500
                );
            }

            // Tạo user mới với role "member" mặc định
           User::create([
                'role_id' => $memberRole->id,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone ?? null,
                'status' => true, // Mặc định: active
                'password' => Hash::make($request->password), // Hash password trước khi lưu
            ]);

            return ApiResponse::success(
                'User registered successfully',
                201
            );

        } catch (Exception $e) {
            return ApiResponse::error(
                'Registration failed',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * ĐĂNG NHẬP
     *
     * Nhận email và password từ FE
     * Validate qua LoginRequest
     * Kiểm tra credentials với database
     * Nếu đúng, tạo và trả về JWT token
     *
     * @param LoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        try {
            // Lấy credentials từ request (đã validate)
            $credentials = $request->only('email', 'password');

            // Thử xác thực với guard 'api' (sử dụng JWT)
            // Nếu sai email/password sẽ trả về false
            $token = $this->guard()->attempt($credentials);

            if (!$token) {
                return ApiResponse::error(
                    'Login failed',
                    'Email hoặc mật khẩu không chính xác',
                    401 // Status code 401: Unauthorized
                );
            }

            // Đăng nhập thành công, lấy thông tin user
            $user = $this->guard()->user();

            return ApiResponse::success(
                'Login successfully',
                [
                    'user' => $user,
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => config('jwt.ttl') * 60
                ],
                200
            );

        } catch (Exception $e) {
            return ApiResponse::error(
                'Login failed',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * LẤY THÔNG TIN USER ĐANG ĐĂNG NHẬP
     *
     * Route này cần authentication (có middleware)
     * FE gửi token trong header: Authorization: Bearer {token}
     * Trả về thông tin user hiện tại
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        try {
            // auth('api')->user() lấy user từ token
            /** @var \App\Models\User $user */
            $user = $this->guard()->user();
            $user->load('role');

            return ApiResponse::success(
                'User retrieved successfully',
                $user,
                200
            );

        } catch (Exception $e) {
            return ApiResponse::error(
                'Failed to retrieve user',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * ĐĂNG XUẤT
     *
     * Route này cần authentication
     * Vô hiệu hóa token hiện tại
     * User sẽ cần đăng nhập lại để lấy token mới
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        try {
            // Vô hiệu hóa token hiện tại
            $this->guard()->logout();

            return ApiResponse::success(
                'Logout successfully',
                null,
                200
            );

        } catch (Exception $e) {
            return ApiResponse::error(
                'Logout failed',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * CẬP NHẬT THÔNG TIN CÁ NHÂN
     *
     * User có thể cập nhật name, phone, password của chính mình
     * Không thể thay đổi email, role_id, status
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $this->guard()->user();

            // Validate
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'name' => ['sometimes', 'string', 'max:255'],
                'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
                'password' => ['sometimes', 'string', 'min:6', 'confirmed'], // required password_confirmation
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            // Cập nhật thông tin
            if ($request->filled('name')) {
                $user->name = $request->name;
            }

            if ($request->has('phone')) {
                $user->phone = $request->phone;
            }

            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            $user->save();
            $user->load('role');

            return ApiResponse::success(
                'Cập nhật thông tin thành công',
                $user,
                200
            );

        } catch (Exception $e) {
            return ApiResponse::error(
                'Cập nhật thất bại',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * LÀM MỚI TOKEN
     *
     * Khi token sắp hết hạn, FE có thể gọi route này để lấy token mới
     * Không cần đăng nhập lại
     * Token cũ sẽ bị vô hiệu hóa
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        try {
            // Tạo token mới và vô hiệu hóa token cũ
            $token = $this->guard()->refresh();

            return ApiResponse::success(
                'Token refreshed successfully',
                [
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => config('jwt.ttl') * 60
                ],
                200
            );

        } catch (Exception $e) {
            return ApiResponse::error(
                'Token refresh failed',
                $e->getMessage(),
                500
            );
        }
    }
}
