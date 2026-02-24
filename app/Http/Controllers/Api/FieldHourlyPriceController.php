<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\FieldCluster;
use App\Models\FieldHourlyPrice;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FieldHourlyPriceController extends Controller
{
    /**
     * Display a listing of the resource.
     * - Admin: xem tất cả
     * - Owner: chỉ xem giá của cụm sân thuộc mình
     */
    public function index()
    {
        try {
            $currentUser = auth('api')->user();

            if (!$currentUser) {
                return ApiResponse::error('Không xác thực được người dùng', null, 401);
            }

            if ($currentUser->role->name_role === 'admin') {
                $prices = FieldHourlyPrice::with('fieldCluster')->get();
            } elseif ($currentUser->role->name_role === 'owner') {
                if (!$currentUser->ownerProfile) {
                    return ApiResponse::error('Bạn chưa có owner profile', null, 400);
                }

                $prices = FieldHourlyPrice::with('fieldCluster')
                    ->whereHas('fieldCluster', function ($query) use ($currentUser) {
                        $query->where('owner_id', $currentUser->ownerProfile->id);
                    })
                    ->get();
            } else {
                return ApiResponse::error('Bạn không có quyền xem bảng giá theo giờ', null, 403);
            }

            return ApiResponse::success('Danh sách giá theo giờ', $prices, 200);
        } catch (Exception $e) {
            return ApiResponse::error('Lấy danh sách thất bại', $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $currentUser = auth('api')->user();

            if (!$currentUser) {
                return ApiResponse::error('Không xác thực được người dùng', null, 401);
            }

            $validator = Validator::make($request->all(), [
                'field_cluster_id' => ['required', 'integer', 'exists:field_clusters,id'],
                'day_type' => ['required', 'in:weekday,weekend'],
                'field_type' => ['required', 'string', 'max:50', 'in:5v5,7v7,11v11'],
                'price_hour' => ['required', 'numeric', 'min:0'],
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            $cluster = FieldCluster::find($request->field_cluster_id);

            if (!$cluster) {
                return ApiResponse::error('Không tìm thấy cụm sân', null, 404);
            }

            if ($currentUser->role->name_role === 'owner') {
                if (!$currentUser->ownerProfile || $cluster->owner_id !== $currentUser->ownerProfile->id) {
                    return ApiResponse::error('Bạn không có quyền thêm giá cho cụm sân này', null, 403);
                }
            }

            $exists = FieldHourlyPrice::where('field_cluster_id', $request->field_cluster_id)
                ->where('day_type', $request->day_type)
                ->where('field_type', $request->field_type)
                ->exists();

            if ($exists) {
                return ApiResponse::error('Giá cho loại sân và loại ngày này đã tồn tại', null, 422);
            }

            $price = FieldHourlyPrice::create([
                'field_cluster_id' => $request->field_cluster_id,
                'day_type' => $request->day_type,
                'field_type' => $request->field_type,
                'price_hour' => $request->price_hour,
            ]);

            return ApiResponse::success('Tạo giá theo giờ thành công', $price->load('fieldCluster'), 201);
        } catch (Exception $e) {
            return ApiResponse::error('Tạo giá thất bại', $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $price = FieldHourlyPrice::with('fieldCluster')->find($id);

            if (!$price) {
                return ApiResponse::error('Không tìm thấy giá theo giờ', null, 404);
            }

            $currentUser = auth('api')->user();

            if ($currentUser->role->name_role === 'owner') {
                if (!$currentUser->ownerProfile || $price->fieldCluster->owner_id !== $currentUser->ownerProfile->id) {
                    return ApiResponse::error('Bạn không có quyền xem giá này', null, 403);
                }
            }

            return ApiResponse::success('Chi tiết giá theo giờ', $price, 200);
        } catch (Exception $e) {
            return ApiResponse::error('Lấy thông tin thất bại', $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $price = FieldHourlyPrice::find($id);

            if (!$price) {
                return ApiResponse::error('Không tìm thấy giá theo giờ', null, 404);
            }

            $currentUser = auth('api')->user();

            if (!$currentUser) {
                return ApiResponse::error('Không xác thực được người dùng', null, 401);
            }

            $currentCluster = FieldCluster::find($price->field_cluster_id);

            if ($currentUser->role->name_role === 'owner') {
                if (!$currentUser->ownerProfile || !$currentCluster || $currentCluster->owner_id !== $currentUser->ownerProfile->id) {
                    return ApiResponse::error('Bạn không có quyền cập nhật giá này', null, 403);
                }
            }

            $validator = Validator::make($request->all(), [
                'field_cluster_id' => ['sometimes', 'integer', 'exists:field_clusters,id'],
                'day_type' => ['sometimes', 'in:weekday,weekend'],
                'field_type' => ['sometimes', 'string', 'max:50', 'in:5v5,7v7,11v11'],
                'price_hour' => ['sometimes', 'numeric', 'min:0'],
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            $newFieldClusterId = $request->filled('field_cluster_id') ? (int) $request->field_cluster_id : $price->field_cluster_id;
            $newDayType = $request->filled('day_type') ? $request->day_type : $price->day_type;
            $newFieldType = $request->filled('field_type') ? $request->field_type : $price->field_type;

            if ($currentUser->role->name_role === 'owner') {
                $newCluster = FieldCluster::find($newFieldClusterId);
                if (!$newCluster || $newCluster->owner_id !== $currentUser->ownerProfile->id) {
                    return ApiResponse::error('Bạn không có quyền gán sang cụm sân này', null, 403);
                }
            }

            $exists = FieldHourlyPrice::where('field_cluster_id', $newFieldClusterId)
                ->where('day_type', $newDayType)
                ->where('field_type', $newFieldType)
                ->where('id', '!=', $price->id)
                ->exists();

            if ($exists) {
                return ApiResponse::error('Giá cho loại sân và loại ngày này đã tồn tại', null, 422);
            }

            if ($request->filled('field_cluster_id')) $price->field_cluster_id = $request->field_cluster_id;
            if ($request->filled('day_type')) $price->day_type = $request->day_type;
            if ($request->filled('field_type')) $price->field_type = $request->field_type;
            if ($request->has('price_hour')) $price->price_hour = $request->price_hour;

            $price->save();

            return ApiResponse::success('Cập nhật giá theo giờ thành công', $price->load('fieldCluster'), 200);
        } catch (Exception $e) {
            return ApiResponse::error('Cập nhật thất bại', $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $price = FieldHourlyPrice::with('fieldCluster')->find($id);

            if (!$price) {
                return ApiResponse::error('Không tìm thấy giá theo giờ', null, 404);
            }

            $currentUser = auth('api')->user();

            if ($currentUser->role->name_role === 'owner') {
                if (!$currentUser->ownerProfile || $price->fieldCluster->owner_id !== $currentUser->ownerProfile->id) {
                    return ApiResponse::error('Bạn không có quyền xóa giá này', null, 403);
                }
            }

            $price->delete();
            return ApiResponse::success('Xóa giá theo giờ thành công', null, 200);
        } catch (Exception $e) {
            return ApiResponse::error('Xóa thất bại', $e->getMessage(), 500);
        }
    }
}
