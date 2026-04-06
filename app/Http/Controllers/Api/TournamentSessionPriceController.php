<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TournamentSessionPrice;
use App\Models\FieldCluster;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TournamentSessionPriceController extends Controller
{
    /**
     * Display a listing of the resource.
     * - Admin: xem tất cả giá
     * - Owner: chỉ xem giá của cụm sân thuộc mình
     * - Member: xem tất cả (để tham khảo)
     */
    public function index(Request $request)
    {
        try {
            $currentUser = auth('api')->user();

            if (!$currentUser) {
                return ApiResponse::error('Không xác thực được người dùng', null, 401);
            }

            $query = TournamentSessionPrice::with('fieldCluster');

            // Owner chỉ xem giá của cụm sân mình
            if ($currentUser->role->name_role === 'owner') {
                if (!$currentUser->ownerProfile) {
                    return ApiResponse::error('Bạn chưa có owner profile', null, 400);
                }
                $query->whereHas('fieldCluster', function ($q) use ($currentUser) {
                    $q->where('owner_id', $currentUser->ownerProfile->id);
                });
            }

            // Filter options
            if ($request->has('field_cluster_id')) {
                $query->where('field_cluster_id', $request->field_cluster_id);
            }

            if ($request->has('field_type')) {
                $query->where('field_type', $request->field_type);
            }

            if ($request->has('day_type')) {
                $query->where('day_type', $request->day_type);
            }

            if ($request->has('session_type')) {
                $query->where('session_type', $request->session_type);
            }

            $prices = $query->orderBy('field_cluster_id')
                           ->orderBy('field_type')
                           ->orderBy('day_type')
                           ->orderBy('session_type')
                           ->get();

            return ApiResponse::success('Danh sách giá theo buổi cho giải đấu', $prices);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * - Admin: có thể tạo giá cho bất kỳ cụm sân nào
     * - Owner: chỉ được tạo giá cho cụm sân của mình
     */
    public function store(Request $request)
    {
        try {
            $currentUser = auth('api')->user();

            if (!$currentUser) {
                return ApiResponse::error('Không xác thực được người dùng', null, 401);
            }

            $validated = $request->validate([
                'field_cluster_id' => 'required|exists:field_clusters,id',
                'field_type' => 'required|in:5v5,7v7,11v11',
                'day_type' => 'required|in:weekday,weekend',
                'session_type' => 'required|in:morning,afternoon,evening,full_day',
                'price_per_session' => 'required|numeric|min:0',
            ]);

            // Lấy field cluster
            $cluster = FieldCluster::find($validated['field_cluster_id']);

            if (!$cluster) {
                return ApiResponse::error('Không tìm thấy cụm sân', null, 404);
            }

            // Kiểm tra quyền Owner
            if ($currentUser->role->name_role === 'owner') {
                if (!$currentUser->ownerProfile) {
                    return ApiResponse::error('Bạn chưa có owner profile', null, 400);
                }
                if ($cluster->owner_id !== $currentUser->ownerProfile->id) {
                    return ApiResponse::error('Bạn không có quyền thêm giá cho cụm sân này', null, 403);
                }
            }

            // Kiểm tra trùng
            $exists = TournamentSessionPrice::where('field_cluster_id', $validated['field_cluster_id'])
                ->where('field_type', $validated['field_type'])
                ->where('day_type', $validated['day_type'])
                ->where('session_type', $validated['session_type'])
                ->exists();

            if ($exists) {
                return ApiResponse::error('Giá cho cấu hình này đã tồn tại. Vui lòng cập nhật giá cũ thay vì tạo mới.', null, 422);
            }

            $price = TournamentSessionPrice::create($validated);

            return ApiResponse::success('Tạo giá theo buổi thành công', $price->load('fieldCluster'), 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $price = TournamentSessionPrice::with('fieldCluster')->find($id);

            if (!$price) {
                return ApiResponse::error('Không tìm thấy giá theo buổi', null, 404);
            }

            $currentUser = auth('api')->user();

            if (!$currentUser) {
                return ApiResponse::error('Không xác thực được người dùng', null, 401);
            }

            // Owner chỉ xem giá của cụm sân mình
            if ($currentUser->role->name_role === 'owner') {
                if (!$currentUser->ownerProfile || $price->fieldCluster->owner_id !== $currentUser->ownerProfile->id) {
                    return ApiResponse::error('Bạn không có quyền xem giá này', null, 403);
                }
            }

            return ApiResponse::success('Chi tiết giá theo buổi', $price);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * - Admin: có thể cập nhật bất kỳ giá nào
     * - Owner: chỉ được cập nhật giá của cụm sân mình
     */
    public function update(Request $request, string $id)
    {
        try {
            $price = TournamentSessionPrice::with('fieldCluster')->find($id);

            if (!$price) {
                return ApiResponse::error('Không tìm thấy giá theo buổi', null, 404);
            }

            $currentUser = auth('api')->user();

            if (!$currentUser) {
                return ApiResponse::error('Không xác thực được người dùng', null, 401);
            }

            // Kiểm tra quyền Owner
            if ($currentUser->role->name_role === 'owner') {
                if (!$currentUser->ownerProfile) {
                    return ApiResponse::error('Bạn chưa có owner profile', null, 400);
                }
                if ($price->fieldCluster->owner_id !== $currentUser->ownerProfile->id) {
                    return ApiResponse::error('Bạn không có quyền cập nhật giá này', null, 403);
                }
            }

            $validated = $request->validate([
                'price_per_session' => 'required|numeric|min:0',
            ]);

            $price->update($validated);

            return ApiResponse::success('Cập nhật giá thành công', $price->load('fieldCluster'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * - Admin: có thể xóa bất kỳ giá nào
     * - Owner: chỉ được xóa giá của cụm sân mình
     */
    public function destroy(string $id)
    {
        try {
            $price = TournamentSessionPrice::with('fieldCluster')->find($id);

            if (!$price) {
                return ApiResponse::error('Không tìm thấy giá theo buổi', null, 404);
            }

            $currentUser = auth('api')->user();

            if (!$currentUser) {
                return ApiResponse::error('Không xác thực được người dùng', null, 401);
            }

            // Kiểm tra quyền Owner
            if ($currentUser->role->name_role === 'owner') {
                if (!$currentUser->ownerProfile) {
                    return ApiResponse::error('Bạn chưa có owner profile', null, 400);
                }
                if ($price->fieldCluster->owner_id !== $currentUser->ownerProfile->id) {
                    return ApiResponse::error('Bạn không có quyền xóa giá này', null, 403);
                }
            }

            $price->delete();

            return ApiResponse::success('Xóa giá thành công', null);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }
}
