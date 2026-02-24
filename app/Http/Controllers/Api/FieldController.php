<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Field;
use App\Models\FieldCluster;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FieldController extends Controller
{
    /**
     * Display a listing of the resource.
     * - Admin: xem tất cả sân con
     * - Owner: chỉ xem sân con thuộc cụm sân của mình
     */
    public function index()
    {
        try {
            $currentUser = auth('api')->user();

            if (!$currentUser) {
                return ApiResponse::error('Không xác thực được người dùng', null, 401);
            }

            if ($currentUser->role->name_role === 'admin') {
                $fields = Field::with('fieldCluster')->get();
            } elseif ($currentUser->role->name_role === 'owner') {
                if (!$currentUser->ownerProfile) {
                    return ApiResponse::error('Bạn chưa có owner profile', null, 400);
                }

                $fields = Field::with('fieldCluster')
                    ->whereHas('fieldCluster', function ($query) use ($currentUser) {
                        $query->where('owner_id', $currentUser->ownerProfile->id);
                    })
                    ->get();
            } else {
                return ApiResponse::error('Bạn không có quyền xem danh sách sân con', null, 403);
            }

            return ApiResponse::success('Danh sách sân con', $fields, 200);
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
                'field_type' => ['required', 'string', 'max:50', 'in:5v5,7v7,11v11'],
                'status' => ['required', 'in:available,maintenance,inactive'],
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
                    return ApiResponse::error('Bạn không có quyền thêm sân con vào cụm sân này', null, 403);
                }
            }

            $field = Field::create([
                'field_cluster_id' => $request->field_cluster_id,
                'field_type' => $request->field_type,
                'status' => $request->status,
            ]);

            return ApiResponse::success('Tạo sân con thành công', $field->load('fieldCluster'), 201);
        } catch (Exception $e) {
            return ApiResponse::error('Tạo sân con thất bại', $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $field = Field::with('fieldCluster')->find($id);

            if (!$field) {
                return ApiResponse::error('Không tìm thấy sân con', null, 404);
            }

            $currentUser = auth('api')->user();

            if ($currentUser->role->name_role === 'owner') {
                if (!$currentUser->ownerProfile || $field->fieldCluster->owner_id !== $currentUser->ownerProfile->id) {
                    return ApiResponse::error('Bạn không có quyền xem sân con này', null, 403);
                }
            }

            return ApiResponse::success('Chi tiết sân con', $field, 200);
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
            $field = Field::find($id);

            if (!$field) {
                return ApiResponse::error('Không tìm thấy sân con', null, 404);
            }

            $currentUser = auth('api')->user();

            if (!$currentUser) {
                return ApiResponse::error('Không xác thực được người dùng', null, 401);
            }

            $currentCluster = FieldCluster::find($field->field_cluster_id);

            if ($currentUser->role->name_role === 'owner') {
                if (!$currentUser->ownerProfile || !$currentCluster || $currentCluster->owner_id !== $currentUser->ownerProfile->id) {
                    return ApiResponse::error('Bạn không có quyền cập nhật sân con này', null, 403);
                }
            }

            $validator = Validator::make($request->all(), [
                'field_cluster_id' => ['sometimes', 'integer', 'exists:field_clusters,id'],
                'field_type' => ['sometimes', 'string', 'max:50', 'in:5v5,7v7,11v11'],
                'status' => ['sometimes', 'in:available,maintenance,inactive'],
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            if ($request->filled('field_cluster_id')) {
                $newCluster = FieldCluster::find($request->field_cluster_id);

                if (!$newCluster) {
                    return ApiResponse::error('Không tìm thấy cụm sân mới', null, 404);
                }

                if ($currentUser->role->name_role === 'owner' && $newCluster->owner_id !== $currentUser->ownerProfile->id) {
                    return ApiResponse::error('Bạn không có quyền chuyển sân sang cụm sân này', null, 403);
                }

                $field->field_cluster_id = $request->field_cluster_id;
            }

            if ($request->filled('field_type')) $field->field_type = $request->field_type;
            if ($request->filled('status')) $field->status = $request->status;

            $field->save();

            return ApiResponse::success('Cập nhật sân con thành công', $field->load('fieldCluster'), 200);
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
            $field = Field::with('fieldCluster')->find($id);

            if (!$field) {
                return ApiResponse::error('Không tìm thấy sân con', null, 404);
            }

            $currentUser = auth('api')->user();

            if ($currentUser->role->name_role === 'owner') {
                if (!$currentUser->ownerProfile || $field->fieldCluster->owner_id !== $currentUser->ownerProfile->id) {
                    return ApiResponse::error('Bạn không có quyền xóa sân con này', null, 403);
                }
            }

            $field->delete();

            return ApiResponse::success('Xóa sân con thành công', null, 200);
        } catch (Exception $e) {
            return ApiResponse::error('Xóa thất bại', $e->getMessage(), 500);
        }
    }
}
