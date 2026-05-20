<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\FieldCluster;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FieldClusterController extends Controller
{
    /**
     * Display a listing of the resource.
     * - Admin: xem tất cả cụm sân
     * - Owner: chỉ xem cụm sân của mình
     */
    public function index()
    {
        try {
            $currentUser = auth('api')->user();

            if (!$currentUser) {
                return ApiResponse::error('Không xác thực được người dùng', null, 401);
            }

            if ($currentUser->role->name_role === 'admin') {
                $clusters = FieldCluster::with(['owner.user', 'fields', 'hourlyPrices'])->get();
            } elseif ($currentUser->role->name_role === 'owner') {
                if (!$currentUser->ownerProfile) {
                    return ApiResponse::error('Bạn chưa có owner profile', null, 400);
                }

                $clusters = FieldCluster::with(['owner.user', 'fields', 'hourlyPrices'])
                    ->where('owner_id', $currentUser->ownerProfile->id)
                    ->get();
            } else {
                return ApiResponse::error('Bạn không có quyền xem danh sách cụm sân', null, 403);
            }

            return ApiResponse::success('Danh sách cụm sân', $clusters, 200);
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
                'owner_id' => ['sometimes', 'integer', 'exists:owner_profiles,id'],
                'name' => ['required', 'string', 'max:255'],
                'address' => ['required', 'string'],
                'phone' => ['nullable', 'string', 'max:20'],
                'description' => ['nullable', 'string'],
                'image' => ['nullable', 'string'],
                'open_time' => ['required', 'date_format:H:i:s'],
                'close_time' => ['required', 'date_format:H:i:s', 'after:open_time'],
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            $ownerId = null;

            if ($currentUser->role->name_role === 'admin') {
                if (!$request->filled('owner_id')) {
                    return ApiResponse::error('Admin cần truyền owner_id', null, 422);
                }
                $ownerId = (int) $request->owner_id;
            } elseif ($currentUser->role->name_role === 'owner') {
                if (!$currentUser->ownerProfile) {
                    return ApiResponse::error('Bạn chưa có owner profile', null, 400);
                }
                $ownerId = $currentUser->ownerProfile->id;
            } else {
                return ApiResponse::error('Bạn không có quyền tạo cụm sân', null, 403);
            }

            // Mỗi owner chỉ được quản lý 1 cụm sân.
            $existingCluster = FieldCluster::where('owner_id', $ownerId)->first();
            if ($existingCluster) {
                return ApiResponse::error('Mỗi chủ sân chỉ được quản lý 1 cụm sân', [
                    'owner_id' => $ownerId,
                    'existing_cluster_id' => $existingCluster->id,
                    'existing_cluster_name' => $existingCluster->name,
                ], 422);
            }

            $cluster = FieldCluster::create([
                'owner_id' => $ownerId,
                'name' => $request->name,
                'address' => $request->address,
                'phone' => $request->phone,
                'description' => $request->description,
                'image' => $request->image,
                'open_time' => $request->open_time,
                'close_time' => $request->close_time,
            ]);

            return ApiResponse::success('Tạo cụm sân thành công', $cluster->load('owner.user'), 201);
        } catch (Exception $e) {
            return ApiResponse::error('Tạo cụm sân thất bại', $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $cluster = FieldCluster::with(['owner.user', 'fields', 'hourlyPrices'])->find($id);

            if (!$cluster) {
                return ApiResponse::error('Không tìm thấy cụm sân', null, 404);
            }

            $currentUser = auth('api')->user();

            if ($currentUser->role->name_role === 'owner') {
                if (!$currentUser->ownerProfile || $cluster->owner_id !== $currentUser->ownerProfile->id) {
                    return ApiResponse::error('Bạn không có quyền xem cụm sân này', null, 403);
                }
            }

            return ApiResponse::success('Chi tiết cụm sân', $cluster, 200);
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
            $cluster = FieldCluster::find($id);

            if (!$cluster) {
                return ApiResponse::error('Không tìm thấy cụm sân', null, 404);
            }

            $currentUser = auth('api')->user();

            if (!$currentUser) {
                return ApiResponse::error('Không xác thực được người dùng', null, 401);
            }

            if ($currentUser->role->name_role === 'owner') {
                if (!$currentUser->ownerProfile || $cluster->owner_id !== $currentUser->ownerProfile->id) {
                    return ApiResponse::error('Bạn không có quyền cập nhật cụm sân này', null, 403);
                }

                if ($request->has('owner_id')) {
                    return ApiResponse::error('Bạn không được phép thay đổi owner_id', null, 403);
                }
            }

            $validator = Validator::make($request->all(), [
                'owner_id' => ['sometimes', 'integer', 'exists:owner_profiles,id'],
                'name' => ['sometimes', 'string', 'max:255'],
                'address' => ['sometimes', 'string'],
                'phone' => ['nullable', 'string', 'max:20'],
                'description' => ['nullable', 'string'],
                'image' => ['nullable', 'string'],
                'open_time' => ['sometimes', 'date_format:H:i:s'],
                'close_time' => ['sometimes', 'date_format:H:i:s'],
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            $newOpenTime = $request->filled('open_time') ? $request->open_time : $cluster->open_time;
            $newCloseTime = $request->filled('close_time') ? $request->close_time : $cluster->close_time;

            if (strtotime($newCloseTime) <= strtotime($newOpenTime)) {
                return ApiResponse::error('Giờ đóng cửa phải lớn hơn giờ mở cửa', null, 422);
            }

            if ($currentUser->role->name_role === 'admin' && $request->filled('owner_id')) {
                $ownerHasAnotherCluster = FieldCluster::where('owner_id', $request->owner_id)
                    ->where('id', '!=', $cluster->id)
                    ->exists();

                if ($ownerHasAnotherCluster) {
                    return ApiResponse::error('Owner này đã có cụm sân, không thể gán thêm', null, 422);
                }

                $cluster->owner_id = $request->owner_id;
            }

            if ($request->filled('name')) $cluster->name = $request->name;
            if ($request->filled('address')) $cluster->address = $request->address;
            if ($request->has('phone')) $cluster->phone = $request->phone;
            if ($request->has('description')) $cluster->description = $request->description;
            if ($request->has('image')) $cluster->image = $request->image;
            if ($request->filled('open_time')) $cluster->open_time = $request->open_time;
            if ($request->filled('close_time')) $cluster->close_time = $request->close_time;

            $cluster->save();

            return ApiResponse::success('Cập nhật cụm sân thành công', $cluster->load('owner.user'), 200);
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
            $cluster = FieldCluster::find($id);

            if (!$cluster) {
                return ApiResponse::error('Không tìm thấy cụm sân', null, 404);
            }

            $currentUser = auth('api')->user();

            if ($currentUser->role->name_role === 'owner') {
                if (!$currentUser->ownerProfile || $cluster->owner_id !== $currentUser->ownerProfile->id) {
                    return ApiResponse::error('Bạn không có quyền xóa cụm sân này', null, 403);
                }
            }

            $cluster->delete();

            return ApiResponse::success('Xóa cụm sân thành công', null, 200);
        } catch (Exception $e) {
            return ApiResponse::error('Xóa thất bại', $e->getMessage(), 500);
        }
    }

    /**
     * Public API - Danh sách cụm sân cho khách hàng (không cần đăng nhập)
     */
    public function publicIndex()
    {
        try {
            $clusters = FieldCluster::with(['owner', 'fields', 'hourlyPrices'])
                ->get();

            return ApiResponse::success('Danh sách cụm sân', $clusters, 200);
        } catch (Exception $e) {
            return ApiResponse::error('Lấy danh sách thất bại', $e->getMessage(), 500);
        }
    }

    /**
     * Public API - Chi tiết cụm sân cho khách hàng (không cần đăng nhập)
     */
    public function publicShow(string $id)
    {
        try {
            $cluster = FieldCluster::with(['owner', 'fields', 'hourlyPrices'])->find($id);

            if (!$cluster) {
                return ApiResponse::error('Không tìm thấy cụm sân', null, 404);
            }

            // DEBUG: Log số lượng fields
            Log::info("FieldCluster ID {$id} has " . $cluster->fields->count() . " fields");
            Log::info("Fields data: " . json_encode($cluster->fields->pluck('id', 'name')));

            return ApiResponse::success('Chi tiết cụm sân', $cluster, 200);
        } catch (Exception $e) {
            return ApiResponse::error('Lấy thông tin thất bại', $e->getMessage(), 500);
        }
    }
}
