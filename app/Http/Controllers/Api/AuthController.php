<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Helpers\ApiResponse;
use App\Mail\UserRegistrationMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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

            // Tạo token xác nhận email (64 ký tự ngẫu nhiên)
            $verificationToken = Str::random(64);

            // Tạo user mới với role "member" mặc định (chưa xác nhận email)
            $user = User::create([
                'role_id' => $memberRole->id,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone ?? null,
                'status' => true,
                'password' => Hash::make($request->password),
                'email_verification_token' => $verificationToken,
            ]);

            // Gửi email xác nhận kèm thông tin tài khoản
            $verificationUrl = url('/api/auth/verify-email/' . $verificationToken);
            try {
                Mail::to($user->email)->send(new UserRegistrationMail(
                    userName: $user->name,
                    userEmail: $user->email,
                    userPassword: $request->password,
                    verificationUrl: $verificationUrl,
                ));
            } catch (Exception $mailError) {
                Log::warning('Failed to send registration verification email: ' . $mailError->getMessage());
            }

            return ApiResponse::success(
                'Đăng ký thành công! Vui lòng kiểm tra email để xác nhận tài khoản trước khi đăng nhập.',
                ['email' => $user->email],
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
            /** @var \App\Models\User $user */
            $user = $this->guard()->user();

            // Kiểm tra tài khoản có bị khóa không
            if (!$user->status) {
                $this->guard()->logout();
                return ApiResponse::error(
                    'Login failed',
                    'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.',
                    403
                );
            }

            // Chỉ member mới đăng ký qua web mới cần xác nhận email
            $user->load('role');
            if ($user->role->name_role === 'member' && !$user->email_verified_at) {
                $this->guard()->logout();
                return ApiResponse::error(
                    'Login failed',
                    'Tài khoản chưa xác nhận email. Vui lòng kiểm tra hộp thư và nhấn link xác nhận.',
                    403
                );
            }

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

    /**
     * XÁC NHẬN EMAIL
     *
     * User nhấn link trong email → kích hoạt tài khoản
     * GET /api/auth/verify-email/{token}
     */
    public function verifyEmail(string $token)
    {
        $frontendLoginUrl = env('FRONTEND_URL', 'http://localhost:8080') . '/auth';

        $user = User::where('email_verification_token', $token)->first();

        if (!$user) {
            return redirect($frontendLoginUrl . '?verified=0&reason=invalid');
        }

        // Kiểm tra token hết hạn (24 giờ kể từ khi tạo tài khoản)
        if ($user->created_at->addHours(24)->isPast()) {
            return redirect($frontendLoginUrl . '?verified=0&reason=expired');
        }

        if ($user->email_verified_at) {
            return redirect($frontendLoginUrl . '?verified=1&reason=already');
        }

        $user->email_verified_at = now();
        $user->email_verification_token = null;
        $user->save();

        return redirect($frontendLoginUrl . '?verified=1');
    }
}

