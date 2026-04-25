<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Lấy danh sách thông báo của user hiện tại
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();

            $query = Notification::where('user_id', $user->id);

            // Filter theo trạng thái đọc/chưa đọc
            if ($request->has('is_read')) {
                $isRead = filter_var($request->is_read, FILTER_VALIDATE_BOOLEAN);
                $query->where('is_read', $isRead);
            }

            // Filter theo type
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            $notifications = $query->orderBy('created_at', 'desc')->paginate(20);

            return ApiResponse::success('Danh sách thông báo', $notifications);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Đếm số thông báo chưa đọc
     */
    public function unreadCount()
    {
        try {
            $user = Auth::user();
            $count = Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->count();

            return ApiResponse::success('Số thông báo chưa đọc', ['unread_count' => $count]);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Đánh dấu 1 thông báo đã đọc
     */
    public function markAsRead($id)
    {
        try {
            $user = Auth::user();
            $notification = Notification::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $notification->markAsRead();

            return ApiResponse::success('Đã đánh dấu đọc', $notification);
        } catch (\Exception $e) {
            return ApiResponse::error('Không tìm thấy thông báo', null, 404);
        }
    }

    /**
     * Đánh dấu tất cả thông báo đã đọc
     */
    public function markAllAsRead()
    {
        try {
            $user = Auth::user();
            Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);

            return ApiResponse::success('Đã đánh dấu tất cả thông báo là đã đọc');
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Xóa 1 thông báo
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $notification = Notification::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $notification->delete();

            return ApiResponse::success('Đã xóa thông báo');
        } catch (\Exception $e) {
            return ApiResponse::error('Không tìm thấy thông báo', null, 404);
        }
    }

    /**
     * Xóa tất cả thông báo đã đọc
     */
    public function deleteAllRead()
    {
        try {
            $user = Auth::user();
            Notification::where('user_id', $user->id)
                ->where('is_read', true)
                ->delete();

            return ApiResponse::success('Đã xóa tất cả thông báo đã đọc');
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }
}
