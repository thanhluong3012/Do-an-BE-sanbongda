<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Field;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     * - Admin: xem tất cả booking
     * - Owner: xem booking của sân thuộc về họ
     * - User: xem booking của chính họ
     */
    public function index()
    {
        try {
            $currentUser = auth('api')->user();

            if (!$currentUser) {
                return ApiResponse::error('Không xác thực được người dùng', null, 401);
            }

            if ($currentUser->role->name_role === 'admin') {
                $books = Book::with(['user', 'field.fieldCluster'])->get();
            } elseif ($currentUser->role->name_role === 'owner') {
                if (!$currentUser->ownerProfile) {
                    return ApiResponse::error('Bạn chưa có owner profile', null, 400);
                }

                $books = Book::with(['user', 'field.fieldCluster'])
                    ->whereHas('field.fieldCluster', function ($query) use ($currentUser) {
                        $query->where('owner_id', $currentUser->ownerProfile->id);
                    })
                    ->get();
            } else {
                // User thường chỉ xem booking của mình
                $books = Book::with(['user', 'field.fieldCluster'])
                    ->where('user_id', $currentUser->id)
                    ->get();
            }

            return ApiResponse::success('Danh sách đặt sân', $books, 200);
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
                'field_id' => ['required', 'integer', 'exists:fields,id'],
                'day_book' => ['required', 'date', 'after_or_equal:today'],
                'start_time' => ['required', 'date_format:H:i'],
                'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
                'total_price' => ['required', 'numeric', 'min:0'],
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            $field = Field::find($request->field_id);

            if (!$field) {
                return ApiResponse::error('Không tìm thấy sân', null, 404);
            }

            // Kiểm tra sân có available không
            if ($field->status !== 'available') {
                return ApiResponse::error('Sân không khả dụng để đặt', null, 400);
            }

            // Kiểm tra trùng lịch (bao gồm cả tournament bookings và regular bookings)
            $conflictBooking = Book::where('field_id', $request->field_id)
                ->where('day_book', $request->day_book)
                ->where(function ($query) use ($request) {
                    $query->whereBetween('start_time', [$request->start_time, $request->end_time])
                        ->orWhereBetween('end_time', [$request->start_time, $request->end_time])
                        ->orWhere(function ($q) use ($request) {
                            $q->where('start_time', '<=', $request->start_time)
                                ->where('end_time', '>=', $request->end_time);
                        });
                })
                ->exists();

            if ($conflictBooking) {
                return ApiResponse::error('Khung giờ này đã được đặt', null, 400);
            }

            $book = Book::create([
                'user_id' => $currentUser->id,
                'booking_type' => 'regular',
                'field_id' => $request->field_id,
                'day_book' => $request->day_book,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'total_price' => $request->total_price,
            ]);

            return ApiResponse::success('Đặt sân thành công', $book->load(['user', 'field.fieldCluster']), 201);
        } catch (Exception $e) {
            return ApiResponse::error('Đặt sân thất bại', $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $book = Book::with(['user', 'field.fieldCluster'])->find($id);

            if (!$book) {
                return ApiResponse::error('Không tìm thấy booking', null, 404);
            }

            $currentUser = auth('api')->user();

            if (!$currentUser) {
                return ApiResponse::error('Không xác thực được người dùng', null, 401);
            }

            // User thường chỉ xem booking của mình
            if ($currentUser->role->name_role !== 'admin' && $currentUser->role->name_role !== 'owner') {
                if ($book->user_id !== $currentUser->id) {
                    return ApiResponse::error('Bạn không có quyền xem booking này', null, 403);
                }
            }

            // Owner chỉ xem booking của sân thuộc về họ
            if ($currentUser->role->name_role === 'owner') {
                if (!$currentUser->ownerProfile || $book->field->fieldCluster->owner_id !== $currentUser->ownerProfile->id) {
                    return ApiResponse::error('Bạn không có quyền xem booking này', null, 403);
                }
            }

            return ApiResponse::success('Chi tiết đặt sân', $book, 200);
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
            $book = Book::find($id);

            if (!$book) {
                return ApiResponse::error('Không tìm thấy booking', null, 404);
            }

            $currentUser = auth('api')->user();

            if (!$currentUser) {
                return ApiResponse::error('Không xác thực được người dùng', null, 401);
            }

            // User thường chỉ cập nhật booking của mình
            if ($currentUser->role->name_role !== 'admin' && $currentUser->role->name_role !== 'owner') {
                if ($book->user_id !== $currentUser->id) {
                    return ApiResponse::error('Bạn không có quyền cập nhật booking này', null, 403);
                }
            }

            // Owner chỉ cập nhật booking của sân thuộc về họ
            if ($currentUser->role->name_role === 'owner') {
                if (!$currentUser->ownerProfile || $book->field->fieldCluster->owner_id !== $currentUser->ownerProfile->id) {
                    return ApiResponse::error('Bạn không có quyền cập nhật booking này', null, 403);
                }
            }

            $validator = Validator::make($request->all(), [
                'field_id' => ['sometimes', 'integer', 'exists:fields,id'],
                'day_book' => ['sometimes', 'date', 'after_or_equal:today'],
                'start_time' => ['sometimes', 'date_format:H:i'],
                'end_time' => ['sometimes', 'date_format:H:i', 'after:start_time'],
                'total_price' => ['sometimes', 'numeric', 'min:0'],
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            // Nếu thay đổi field_id, kiểm tra field mới
            if ($request->filled('field_id')) {
                $field = Field::find($request->field_id);

                if (!$field) {
                    return ApiResponse::error('Không tìm thấy sân', null, 404);
                }

                if ($field->status !== 'available') {
                    return ApiResponse::error('Sân không khả dụng để đặt', null, 400);
                }

                $book->field_id = $request->field_id;
            }

            // Cập nhật các trường khác
            if ($request->filled('day_book')) $book->day_book = $request->day_book;
            if ($request->filled('start_time')) $book->start_time = $request->start_time;
            if ($request->filled('end_time')) $book->end_time = $request->end_time;
            if ($request->filled('total_price')) $book->total_price = $request->total_price;

            // Kiểm tra trùng lịch (trừ booking hiện tại)
            if ($request->filled('field_id') || $request->filled('day_book') || $request->filled('start_time') || $request->filled('end_time')) {
                $conflictBooking = Book::where('field_id', $book->field_id)
                    ->where('day_book', $book->day_book)
                    ->where('id', '!=', $book->id)
                    ->where(function ($query) use ($book) {
                        $query->whereBetween('start_time', [$book->start_time, $book->end_time])
                            ->orWhereBetween('end_time', [$book->start_time, $book->end_time])
                            ->orWhere(function ($q) use ($book) {
                                $q->where('start_time', '<=', $book->start_time)
                                    ->where('end_time', '>=', $book->end_time);
                            });
                    })
                    ->exists();

                if ($conflictBooking) {
                    return ApiResponse::error('Khung giờ này đã được đặt', null, 400);
                }
            }

            $book->save();

            return ApiResponse::success('Cập nhật booking thành công', $book->load(['user', 'field.fieldCluster']), 200);
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
            $book = Book::with(['user', 'field.fieldCluster'])->find($id);

            if (!$book) {
                return ApiResponse::error('Không tìm thấy booking', null, 404);
            }

            $currentUser = auth('api')->user();

            if (!$currentUser) {
                return ApiResponse::error('Không xác thực được người dùng', null, 401);
            }

            // User thường chỉ xóa booking của mình
            if ($currentUser->role->name_role !== 'admin' && $currentUser->role->name_role !== 'owner') {
                if ($book->user_id !== $currentUser->id) {
                    return ApiResponse::error('Bạn không có quyền xóa booking này', null, 403);
                }
            }

            // Owner chỉ xóa booking của sân thuộc về họ
            if ($currentUser->role->name_role === 'owner') {
                if (!$currentUser->ownerProfile || $book->field->fieldCluster->owner_id !== $currentUser->ownerProfile->id) {
                    return ApiResponse::error('Bạn không có quyền xóa booking này', null, 403);
                }
            }

            $book->delete();

            return ApiResponse::success('Hủy đặt sân thành công', null, 200);
        } catch (Exception $e) {
            return ApiResponse::error('Xóa thất bại', $e->getMessage(), 500);
        }
    }
}
