<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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
        // Kiểm tra user đã đăng nhập chưa
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Chưa đăng nhập'
            ], 401);
        }

        $user = auth()->user();

        // Kiểm tra user có role không
        if (!$user->role) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản chưa được phân quyền'
            ], 403);
        }

        // Kiểm tra role của user có trong danh sách cho phép không
        if (!in_array($user->role->name, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền truy cập chức năng này'
            ], 403);
        }

        return $next($request);
    }
}
