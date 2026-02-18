<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  // Các role được phép truy cập
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        try {
            // Lấy user từ JWT token
            $user = JWTAuth::parseToken()->authenticate();

            // Kiểm tra user có tồn tại không
            if (!$user) {
                return ApiResponse::error('Chưa đăng nhập', null, 401);
            }

            // Kiểm tra user có role không
            if (!$user->role) {
                return ApiResponse::error('Tài khoản chưa được phân quyền', null, 403);
            }

            // Kiểm tra role của user có trong danh sách cho phép không
            // So sánh name_role (lowercase) với roles truyền vào
            if (!in_array(strtolower($user->role->name_role), array_map('strtolower', $roles))) {
                return ApiResponse::error('Bạn không có quyền truy cập chức năng này', null, 403);
            }

            return $next($request);

        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return ApiResponse::error('Token đã hết hạn', null, 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return ApiResponse::error('Token không hợp lệ', null, 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return ApiResponse::error('Token không tồn tại', null, 401);
        }
    }
}
