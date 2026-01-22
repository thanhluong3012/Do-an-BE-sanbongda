<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use App\Helpers\ApiResponse;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     * Middleware này kiểm tra JWT token có hợp lệ không
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Kiểm tra xem token có trong request không
            // JWTAuth tự động lấy token từ header "Authorization: Bearer {token}"
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json(
                    ApiResponse::error('User not found', 'Token không hợp lệ', 404),
                    404
                );
            }

        } catch (TokenExpiredException $e) {
            // Token hết hạn
            return response()->json(
                ApiResponse::error('Token expired', 'Token đã hết hạn, vui lòng đăng nhập lại', 409),
                409
            );

        } catch (TokenInvalidException $e) {
            // Token không hợp lệ
            return response()->json(
                ApiResponse::error('Token invalid', 'Token không hợp lệ', 401),
                401
            );

        } catch (JWTException $e) {
            // Token không tìm thấy trong request
            return response()->json(
                ApiResponse::error('Token absent', 'Vui lòng cung cấp token', 401),
                401
            );
        }

        return $next($request);
    }
}
