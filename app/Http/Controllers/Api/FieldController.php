<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\FieldRequest;
use App\Http\Resources\FieldResource;
use App\Models\Field;
use Exception;
use Illuminate\Http\Request;

class FieldController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $fields = Field::all();
            return ApiResponse::success('Lấy danh sách sân bóng thành công', FieldResource::collection($fields));
        } catch (Exception $e) {
            return ApiResponse::error('Lỗi khi lấy danh sách sân bóng: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FieldRequest $request)
    {
        try {
            $field = Field::create($request->validated());
            return ApiResponse::success('Tạo sân bóng thành công', new FieldResource($field), 201);
        } catch (Exception $e) {
            return ApiResponse::error('Lỗi khi tạo sân bóng: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Field $field)
    {
        try {
            return ApiResponse::success('Lấy thông tin sân bóng thành công', new FieldResource($field));
        } catch (Exception $e) {
            return ApiResponse::error('Lỗi khi lấy thông tin sân bóng: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(FieldRequest $request, Field $field)
    {
        try {
            $field->update($request->validated());
            return ApiResponse::success('Cập nhật sân bóng thành công', new FieldResource($field));
        } catch (Exception $e) {
            return ApiResponse::error('Lỗi khi cập nhật sân bóng: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Field $field)
    {
        try {
            $field->delete();
            return ApiResponse::success('Xóa sân bóng thành công');
        } catch (Exception $e) {
            return ApiResponse::error('Lỗi khi xóa sân bóng: ' . $e->getMessage(), null, 500);
        }
    }
}
