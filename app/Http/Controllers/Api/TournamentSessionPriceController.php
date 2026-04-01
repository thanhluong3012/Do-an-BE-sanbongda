<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TournamentSessionPrice;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TournamentSessionPriceController extends Controller
{
    // Xem danh sách giá
    public function index(Request $request)
    {
        try {
            $query = TournamentSessionPrice::with('fieldCluster');

            if ($request->has('field_cluster_id')) {
                $query->where('field_cluster_id', $request->field_cluster_id);
            }

            if ($request->has('field_type')) {
                $query->where('field_type', $request->field_type);
            }

            $prices = $query->orderBy('field_cluster_id')->orderBy('field_type')->get();

            return ApiResponse::success('Danh sách giá thuê sân giải đấu', $prices);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    // Tạo giá mới (Admin/Owner)
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'field_cluster_id' => 'required|exists:field_clusters,id',
                'field_type' => 'required|in:5v5,7v7,11v11',
                'day_type' => 'required|in:weekday,weekend',
                'session_type' => 'required|in:morning,afternoon,evening,full_day',
                'price_per_session' => 'required|numeric|min:0',
            ]);

            // Kiểm tra trùng
            $exists = TournamentSessionPrice::where('field_cluster_id', $validated['field_cluster_id'])
                ->where('field_type', $validated['field_type'])
                ->where('day_type', $validated['day_type'])
                ->where('session_type', $validated['session_type'])
                ->exists();

            if ($exists) {
                return ApiResponse::error('Giá cho cấu hình này đã tồn tại', 400);
            }

            $price = TournamentSessionPrice::create($validated);

            return ApiResponse::success('Tạo giá thành công', $price->load('fieldCluster'), 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    // Xem chi tiết
    public function show(string $id)
    {
        try {
            $price = TournamentSessionPrice::with('fieldCluster')->findOrFail($id);
            return ApiResponse::success('Chi tiết giá', $price);
        } catch (\Exception $e) {
            return ApiResponse::error('Không tìm thấy', 404);
        }
    }

    // Cập nhật giá
    public function update(Request $request, string $id)
    {
        try {
            $price = TournamentSessionPrice::findOrFail($id);

            $validated = $request->validate([
                'price_per_session' => 'required|numeric|min:0',
            ]);

            $price->update($validated);

            return ApiResponse::success('Cập nhật giá thành công', $price);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    // Xóa giá
    public function destroy(string $id)
    {
        try {
            $price = TournamentSessionPrice::findOrFail($id);
            $price->delete();

            return ApiResponse::success('Xóa giá thành công', null);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }
}
