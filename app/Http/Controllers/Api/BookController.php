<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Field;
use App\Models\Notification;
use App\Models\Tournament;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     * - Admin: xem tất cả booking
     * - Owner: xem booking của sân thuộc về họ
     * - User: xem booking của chính họ
     */
    public function index(Request $request)
    {
        try {
            $currentUser = auth('api')->user();

            if (!$currentUser) {
                return ApiResponse::error('Không xác thực được người dùng', null, 401);
            }

            $query = Book::with(['user', 'field.fieldCluster', 'tournament']);

            if ($currentUser->role->name_role === 'admin') {
                // Admin xem tất cả
            } else if ($currentUser->role->name_role === 'owner') {
                if (!$currentUser->ownerProfile) {
                    return ApiResponse::error('Bạn chưa có owner profile', null, 400);
                }

                $query->whereHas('field.fieldCluster', function ($q) use ($currentUser) {
                    $q->where('owner_id', $currentUser->ownerProfile->id);
                });
            } else {
                // User thường chỉ xem booking của mình
                $query->where('user_id', $currentUser->id);
            }

            // Filter theo payment_status
            if ($request->has('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }

            // Filter theo payment_method
            if ($request->has('payment_method')) {
                $query->where('payment_method', $request->payment_method);
            }

            // 🔥 Filter theo booking_type (regular/tournament) - để phân biệt booking thường và giải đấu
            if ($request->has('booking_type')) {
                $query->where('booking_type', $request->booking_type);
            }

            // 🔥 Filter theo status (pending/confirmed/cancelled/completed)
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter theo ngày
            if ($request->has('from_date')) {
                $query->where('day_book', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->where('day_book', '<=', $request->to_date);
            }

            // Sắp xếp theo ngày booking (mới nhất trước)
            $query->orderBy('day_book', 'desc')->orderBy('start_time', 'desc');

            $books = $query->get();

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
                'start_time' => ['required', 'date_format:H:i:s'],
                'end_time' => ['required', 'date_format:H:i:s', 'after:start_time'],
                'total_price' => ['required', 'numeric', 'min:0'],
                'payment_method' => ['required', 'in:cash,transfer'],
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
                'status' => 'pending',  // 🔥 Trạng thái booking
                'field_id' => $request->field_id,
                'day_book' => $request->day_book,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'total_price' => $request->total_price,
                'payment_status' => 'pending',  // 🔥 Trạng thái thanh toán
                'payment_method' => $request->payment_method,
            ]);

            // Load relationships
            $book->load(['user', 'field.fieldCluster.owner']);

            // 🔔 Tạo thông báo nhắc nhở thanh toán cho user
            try {
                $paymentMethodText = $request->payment_method === 'cash' ? 'tiền mặt tại sân' : 'chuyển khoản và upload bill';
                Notification::create([
                    'user_id' => $currentUser->id,
                    'type' => 'booking_created',
                    'title' => '⚠️ Vui lòng thanh toán để hoàn tất đặt sân',
                    'message' => "Bạn đã đặt sân {$book->field->fieldCluster->name} vào ngày {$book->day_book->format('d/m/Y')} ({$book->start_time} - {$book->end_time}). Vui lòng thanh toán {$paymentMethodText} để hoàn tất booking.",
                    'data' => [
                        'booking_id' => $book->id,
                        'field_name' => $book->field->name ?? '',
                        'field_cluster_name' => $book->field->fieldCluster->name,
                        'day_book' => $book->day_book,
                        'start_time' => $book->start_time,
                        'end_time' => $book->end_time,
                        'payment_method' => $request->payment_method,
                        'payment_status' => 'pending',
                        'total_price' => $request->total_price,
                        'action_required' => 'payment',
                    ],
                ]);
            } catch (Exception $notifError) {
                Log::warning('Failed to create booking notification: ' . $notifError->getMessage());
            }

            // Chuẩn bị payment info với QR code
            $fieldCluster = $book->field->fieldCluster;
            $paymentInfo = [
                'status' => 'pending',
                'payment_method' => $request->payment_method,
                'total_price' => $request->total_price,
            ];

            // Nếu thanh toán qua transfer → trả về QR code của chủ sân
            if ($request->payment_method === 'transfer') {
                if ($fieldCluster->owner && $fieldCluster->owner->qr_code_image) {
                    $paymentInfo['qr_code_url'] = $fieldCluster->owner->qr_code_image;
                    $paymentInfo['instruction'] = "Vui lòng quét mã QR để chuyển khoản ĐÚNG số tiền: " . number_format($request->total_price) . " VNĐ và ghi nội dung: BOOK-{$book->id}";
                    $paymentInfo['transfer_note'] = "BOOK-{$book->id}";
                    $paymentInfo['message'] = 'Vui lòng quét mã QR và chuyển khoản. Sau khi chuyển khoản, upload bill để chủ sân xác nhận.';
                } else {
                    $paymentInfo['message'] = 'Chủ sân chưa cập nhật QR code. Vui lòng liên hệ chủ sân để được hướng dẫn thanh toán.';
                    $paymentInfo['owner_contact'] = [
                        'name' => $fieldCluster->name,
                        'phone' => $fieldCluster->phone,
                        'address' => $fieldCluster->address,
                    ];
                }
            } else {
                // Thanh toán tiền mặt
                $paymentInfo['instruction'] = "Vui lòng liên hệ chủ sân để thanh toán tiền mặt. Số tiền: " . number_format($request->total_price) . " VNĐ";
                $paymentInfo['owner_contact'] = [
                    'name' => $fieldCluster->name,
                    'phone' => $fieldCluster->phone,
                    'address' => $fieldCluster->address,
                ];
                $paymentInfo['message'] = 'Vui lòng liên hệ chủ sân để thanh toán tiền mặt.';
            }

            return ApiResponse::success(
                'Đặt sân thành công! Vui lòng thanh toán với chủ sân và chờ xác nhận thanh toán để hoàn tất đặt sân.',
                [
                    'booking' => $book,
                    'payment_info' => $paymentInfo,
                ],
                201
            );
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
            $book = Book::with([
                'user.role',
                'field.fieldCluster',
                'tournament',
                'confirmedBy.role'
            ])->find($id);

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

            // 🔥 FORMAT RESPONSE CHI TIẾT HỢP
            $dayOfWeek = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'][$book->day_book->dayOfWeek];

            $response = [
                // 🔑 THÔNG TIN BOOKING
                'booking_id' => $book->id,
                'booking_code' => 'BOOK-' . str_pad($book->id, 6, '0', STR_PAD_LEFT),
                'booking_type' => $book->booking_type,
                'booking_type_label' => $book->booking_type_label,
                'status' => $book->status,
                'created_at' => $book->created_at->format('d/m/Y H:i:s'),

                // ⚽ THÔNG TIN SÂN
                'field' => [
                    'id' => $book->field->id,
                    'name' => $book->field->name,
                    'type' => $book->field->type ?? null,
                    'field_cluster' => [
                        'id' => $book->field->fieldCluster->id,
                        'name' => $book->field->fieldCluster->name,
                        'address' => $book->field->fieldCluster->address,
                        'phone' => $book->field->fieldCluster->phone,
                    ],
                ],

                // 📅 THÔNG TIN LỊCH
                'schedule' => [
                    'day_book' => $book->day_book->format('Y-m-d'),
                    'day_book_formatted' => $book->day_book->format('d/m/Y'),
                    'day_of_week' => $dayOfWeek,
                    'start_time' => substr($book->start_time, 0, 5),
                    'end_time' => substr($book->end_time, 0, 5),
                    'duration_hours' => (strtotime($book->end_time) - strtotime($book->start_time)) / 3600,
                ],

                // 💰 THÔNG TIN THANH TOÁN
                'payment' => [
                    'total_price' => $book->total_price,
                    'total_price_formatted' => number_format($book->total_price) . ' đ',
                    'payment_method' => $book->payment_method,
                    'payment_method_label' => $book->payment_method === 'cash' ? 'Tiền mặt' : 'Chuyển khoản',
                    'payment_status' => $book->payment_status,
                    'payment_status_label' => match($book->payment_status) {
                        'pending' => 'Chờ thanh toán',
                        'paid' => 'Đã thanh toán',
                        'failed' => 'Thanh toán thất bại',
                        default => $book->payment_status,
                    },
                    'payment_proof_url' => $book->payment_proof,
                    'payment_confirmed_at' => $book->payment_confirmed_at?->format('d/m/Y H:i:s'),
                    'payment_note' => $book->payment_note,
                    'confirmed_by' => $book->confirmedBy ? [
                        'id' => $book->confirmedBy->id,
                        'name' => $book->confirmedBy->name,
                        'email' => $book->confirmedBy->email,
                        'role' => $book->confirmedBy->role->name_role,
                    ] : null,
                ],

                // 👤 THÔNG TIN NGƯỜI ĐẶT
                'customer' => [
                    'id' => $book->user->id,
                    'name' => $book->user->name,
                    'email' => $book->user->email,
                    'phone' => $book->user->phone ?? null,
                ],

                // 🏆 THÔNG TIN GIẢI ĐẤU (nếu có)
                'tournament' => $book->tournament ? [
                    'id' => $book->tournament->id,
                    'name' => $book->tournament->name,
                    'status' => $book->tournament->status,
                ] : null,
            ];

            return ApiResponse::success('Chi tiết đặt sân', $response, 200);
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
                'start_time' => ['sometimes', 'date_format:H:i:s'],
                'end_time' => ['sometimes', 'date_format:H:i:s', 'after:start_time'],
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

    /**
     * Upload payment proof (bill chuyển khoản)
     * User upload ảnh bill để chứng minh đã thanh toán
     */
    public function uploadPaymentProof(Request $request, string $id)
    {
        try {

            $book = Book::with(['user', 'field.fieldCluster.owner.user'])->find($id);

            if (!$book) {
                return ApiResponse::error('Không tìm thấy booking', null, 404);
            }

            $currentUser = auth('api')->user();

            if (!$currentUser) {
                return ApiResponse::error('Không xác thực được người dùng', null, 401);
            }

            // Chỉ user sở hữu booking mới được upload
            if ($book->user_id !== $currentUser->id) {
                return ApiResponse::error('Bạn không có quyền upload bill cho booking này', null, 403);
            }

            // Chỉ booking chọn transfer mới cần upload bill
            if ($book->payment_method !== 'transfer') {
                return ApiResponse::error('Booking này thanh toán bằng tiền mặt, không cần upload bill', null, 400);
            }

            // Kiểm tra booking đang ở trạng thái pending hoặc failed (để upload lại sau khi bị từ chối)
            if (!in_array($book->payment_status, ['pending', 'failed'])) {
                return ApiResponse::error('Chỉ có thể upload bill cho booking đang chờ thanh toán hoặc bị từ chối', null, 400);
            }

            $validated = $request->validate([
                'payment_proof_url' => ['required', 'string', 'url', 'max:2048'],
            ]);

            $book->payment_proof = $validated['payment_proof_url'];
            $book->save();

            // Reset payment_status về pending để owner xét lại (trường hợp upload lại sau khi bị từ chối)
            $book->payment_status = 'pending';
            $book->save();

            // 🔔 Tạo thông báo cho user khi upload bill thành công
            try {
                Notification::create([
                    'user_id' => $book->user_id,
                    'type' => 'booking_bill_uploaded',
                    'title' => '✅ Đã upload bill chuyển khoản',
                    'message' => "Bạn đã upload bill chuyển khoản cho đặt sân tại {$book->field->fieldCluster->name} vào ngày {$book->day_book->format('d/m/Y')}. Vui lòng chờ chủ sân xác nhận thanh toán.",
                    'data' => [
                        'booking_id' => $book->id,
                        'field_cluster_name' => $book->field->fieldCluster->name,
                        'payment_proof_url' => $book->payment_proof,
                        'status' => 'waiting_confirmation',
                    ],
                ]);
            } catch (Exception $notifError) {
                Log::warning('Failed to create bill upload notification: ' . $notifError->getMessage());
            }

            // 🔔 Tạo thông báo cho owner để xác nhận thanh toán
            try {
                $fieldCluster = $book->field->fieldCluster;
                if ($fieldCluster->owner && $fieldCluster->owner->user) {
                    Notification::create([
                        'user_id' => $fieldCluster->owner->user->id,
                        'type' => 'booking_payment_pending',
                        'title' => '💰 Có booking cần xác nhận thanh toán',
                        'message' => "User {$book->user->name} đã upload bill chuyển khoản cho booking tại {$fieldCluster->name} vào ngày {$book->day_book->format('d/m/Y')} ({$book->start_time} - {$book->end_time}). Số tiền: " . number_format($book->total_price) . " VNĐ.",
                        'data' => [
                            'booking_id' => $book->id,
                            'user_name' => $book->user->name,
                            'field_cluster_name' => $fieldCluster->name,
                            'day_book' => $book->day_book,
                            'start_time' => $book->start_time,
                            'end_time' => $book->end_time,
                            'total_price' => $book->total_price,
                            'payment_proof_url' => $book->payment_proof,
                            'action_required' => 'confirm_payment',
                        ],
                    ]);
                }
            } catch (Exception $notifError) {
                Log::warning('Failed to create owner notification: ' . $notifError->getMessage());
            }

            return ApiResponse::success(
                'Upload bill thành công. Chờ chủ sân xác nhận thanh toán.',
                [
                    'booking_id' => $book->id,
                    'payment_proof_url' => $book->payment_proof,
                    'payment_status' => $book->payment_status,
                ],
                200
            );
        } catch (Exception $e) {
            return ApiResponse::error('Upload thất bại', $e->getMessage(), 500);
        }
    }

    /**
     * Confirm payment - Owner/Admin xác nhận thanh toán
     */
    public function confirmPayment(Request $request, string $id)
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

            // Chỉ owner của sân hoặc admin mới được xác nhận
            if ($currentUser->role->name_role !== 'admin') {
                if ($currentUser->role->name_role !== 'owner' ||
                    !$currentUser->ownerProfile ||
                    $book->field->fieldCluster->owner_id !== $currentUser->ownerProfile->id) {
                    return ApiResponse::error('Bạn không có quyền xác nhận thanh toán cho booking này', null, 403);
                }
            }

            // Kiểm tra booking đang ở trạng thái pending
            if ($book->payment_status !== 'pending') {
                return ApiResponse::error('Booking này không ở trạng thái chờ thanh toán', null, 400);
            }

            $validator = Validator::make($request->all(), [
                'action' => ['required', 'in:approve,reject'],
                'note' => ['nullable', 'string', 'max:500'],
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            // Nếu là transfer, kiểm tra đã upload bill chưa
            if ($book->payment_method === 'transfer' && !$book->payment_proof && $request->action === 'approve') {
                return ApiResponse::error('User chưa upload bill chuyển khoản. Không thể xác nhận thanh toán.', null, 400);
            }

            // Update payment status
            if ($request->action === 'approve') {
                $book->payment_status = 'paid';
                $book->status = 'confirmed';  // 🔥 UPDATE booking status
                $book->payment_confirmed_at = now();
                $book->confirmed_by_user_id = $currentUser->id;  // 🔥 LƯU NGƯỜI XÁC NHẬN
                $message = 'Xác nhận thanh toán và booking thành công';

                // Nếu booking này thuộc giải đấu, kiểm tra và cập nhật payment_status của giải
                if ($book->tournament_id) {
                    $book->save();
                    $tournament = Tournament::find($book->tournament_id);
                    if ($tournament && $tournament->payment_status !== 'paid') {
                        $pendingCount = Book::where('tournament_id', $book->tournament_id)
                            ->where('payment_status', '!=', 'paid')
                            ->where('id', '!=', $book->id)
                            ->count();
                        if ($pendingCount === 0) {
                            $tournament->payment_status = 'paid';
                            $tournament->payment_date = now();
                            $tournament->save();
                        }
                    }
                }

                // ✅ Tạo thông báo cho user
                try {
                    Notification::create([
                        'user_id' => $book->user_id,
                        'type' => 'booking_payment_approved',
                        'title' => '✅ Thanh toán đặt sân đã được xác nhận',
                        'message' => "Thanh toán cho đặt sân tại {$book->field->fieldCluster->name} vào ngày {$book->day_book->format('d/m/Y')} ({$book->start_time} - {$book->end_time}) đã được chủ sân xác nhận.",
                        'data' => [
                            'booking_id' => $book->id,
                            'field_name' => $book->field->name ?? '',
                            'field_cluster_name' => $book->field->fieldCluster->name,
                            'day_book' => $book->day_book,
                            'start_time' => $book->start_time,
                            'end_time' => $book->end_time,
                            'payment_method' => $book->payment_method,
                            'payment_status' => 'paid',
                            'total_price' => $book->total_price,
                        ],
                    ]);
                } catch (Exception $notifError) {
                    Log::warning('Failed to create booking payment notification: ' . $notifError->getMessage());
                }
            } else {
                $book->payment_status = 'failed';
                // Xóa file bill bị từ chối khỏi storage
                if ($book->payment_proof) {
                    $oldPath = public_path('storage/' . $book->payment_proof);
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }
                $book->payment_proof = null;  // User phải upload lại
                $message = 'Đã từ chối thanh toán';

                // ✅ Tạo thông báo từ chối
                try {
                    Notification::create([
                        'user_id' => $book->user_id,
                        'type' => 'booking_payment_rejected',
                        'title' => '❌ Thanh toán đặt sân bị từ chối',
                        'message' => "Thanh toán cho đặt sân tại {$book->field->fieldCluster->name} vào ngày {$book->day_book->format('d/m/Y')} đã bị từ chối. Lý do: " . ($request->note ?? 'Không có lý do cụ thể'),
                        'data' => [
                            'booking_id' => $book->id,
                            'field_name' => $book->field->name ?? '',
                            'field_cluster_name' => $book->field->fieldCluster->name,
                            'payment_method' => $book->payment_method,
                            'rejected_reason' => $request->note ?? null,
                        ],
                    ]);
                } catch (Exception $notifError) {
                    Log::warning('Failed to create booking payment rejection notification: ' . $notifError->getMessage());
                }
            }

            $book->payment_note = $request->note;
            $book->save();

            return ApiResponse::success(
                $message,
                [
                    'booking_id' => $book->id,
                    'status' => $book->status,  // 🔥 Trả về booking status
                    'payment_status' => $book->payment_status,
                    'payment_confirmed_at' => $book->payment_confirmed_at,
                    'payment_proof' => $book->payment_proof,
                    'note' => $request->note ?? null,
                ],
                200
            );
        } catch (Exception $e) {
            return ApiResponse::error('Xác nhận thất bại', $e->getMessage(), 500);
        }
    }

    /**
     * Lấy thông tin thanh toán của booking (bao gồm QR code)
     */
    public function getPaymentInfo(string $id)
    {
        try {
            $book = Book::with(['user', 'field.fieldCluster.owner'])->find($id);

            if (!$book) {
                return ApiResponse::error('Không tìm thấy booking', null, 404);
            }

            $currentUser = auth('api')->user();

            if (!$currentUser) {
                return ApiResponse::error('Không xác thực được người dùng', null, 401);
            }

            // User chỉ xem payment info của booking mình
            if ($currentUser->role->name_role !== 'admin' && $currentUser->role->name_role !== 'owner') {
                if ($book->user_id !== $currentUser->id) {
                    return ApiResponse::error('Bạn không có quyền xem thông tin thanh toán này', null, 403);
                }
            }

            // Owner chỉ xem payment info của sân thuộc về họ
            if ($currentUser->role->name_role === 'owner') {
                if (!$currentUser->ownerProfile || $book->field->fieldCluster->owner_id !== $currentUser->ownerProfile->id) {
                    return ApiResponse::error('Bạn không có quyền xem thông tin thanh toán này', null, 403);
                }
            }

            $fieldCluster = $book->field->fieldCluster;
            $paymentInfo = [
                'booking_id' => $book->id,
                'status' => $book->payment_status,
                'payment_method' => $book->payment_method,
                'total_price' => $book->total_price,
                'payment_proof' => $book->payment_proof,
                'payment_confirmed_at' => $book->payment_confirmed_at,
                'payment_note' => $book->payment_note,
            ];

            // Nếu thanh toán qua chuyển khoản và chưa thanh toán → hiển thị QR code
            if ($book->payment_method === 'transfer' && $book->payment_status === 'pending') {
                if ($fieldCluster->owner && $fieldCluster->owner->qr_code_image) {
                    $paymentInfo['qr_code_url'] = $fieldCluster->owner->qr_code_image;
                    $paymentInfo['instruction'] = "Vui lòng quét mã QR để chuyển khoản ĐÚNG số tiền: " . number_format($book->total_price) . " VNĐ và ghi nội dung: BOOK-{$book->id}";
                    $paymentInfo['transfer_note'] = "BOOK-{$book->id}";
                } else {
                    $paymentInfo['message'] = 'Chủ sân chưa cập nhật QR code. Vui lòng liên hệ chủ sân.';
                }
            }

            // Thông tin liên hệ chủ sân
            $paymentInfo['owner_contact'] = [
                'name' => $fieldCluster->name,
                'phone' => $fieldCluster->phone,
                'address' => $fieldCluster->address,
            ];

            return ApiResponse::success('Thông tin thanh toán', $paymentInfo, 200);
        } catch (Exception $e) {
            return ApiResponse::error('Lấy thông tin thất bại', $e->getMessage(), 500);
        }
    }

    /**
     * Lấy danh sách booking chờ xác nhận thanh toán (cho Owner/Admin)
     * - transfer: chỉ hiện booking đã upload bill
     * - cash: hiện tất cả booking cash đang pending
     * Query params: payment_method (cash|transfer), booking_type (regular|tournament)
     */
    public function getPendingPayments(Request $request)
    {
        try {
            $currentUser = auth('api')->user();

            if (!$currentUser) {
                return ApiResponse::error('Không xác thực được người dùng', null, 401);
            }

            // Chỉ owner và admin mới được xem
            if ($currentUser->role->name_role !== 'owner' && $currentUser->role->name_role !== 'admin') {
                return ApiResponse::error('Bạn không có quyền truy cập', null, 403);
            }

            $query = Book::with(['user', 'field.fieldCluster'])
                ->where('payment_status', 'pending')
                ->where(function ($q) {
                    // Lấy transfer booking đã upload bill VÀ cash booking (không cần bill)
                    $q->where(function ($q2) {
                        $q2->where('payment_method', 'transfer')->whereNotNull('payment_proof');
                    })->orWhere('payment_method', 'cash');
                });

            if ($currentUser->role->name_role === 'owner') {
                if (!$currentUser->ownerProfile) {
                    return ApiResponse::error('Bạn chưa có owner profile', null, 400);
                }

                $query->whereHas('field.fieldCluster', function ($q) use ($currentUser) {
                    $q->where('owner_id', $currentUser->ownerProfile->id);
                });
            }

            // Filter tùy chọn theo payment_method
            if ($request->has('payment_method')) {
                $query->where('payment_method', $request->payment_method);
            }

            // Filter tùy chọn theo booking_type
            if ($request->has('booking_type')) {
                $query->where('booking_type', $request->booking_type);
            }

            $books = $query->orderBy('created_at', 'desc')->get();

            return ApiResponse::success(
                'Danh sách booking chờ xác nhận thanh toán',
                [
                    'total' => $books->count(),
                    'transfer_pending' => $books->where('payment_method', 'transfer')->count(),
                    'cash_pending' => $books->where('payment_method', 'cash')->count(),
                    'bookings' => $books,
                ],
                200
            );
        } catch (Exception $e) {
            return ApiResponse::error('Lấy danh sách thất bại', $e->getMessage(), 500);
        }
    }

    /**
     * Thống kê thanh toán (cho Owner/Admin)
     */
    public function getPaymentStatistics()
    {
        try {
            $currentUser = auth('api')->user();

            if (!$currentUser) {
                return ApiResponse::error('Không xác thực được người dùng', null, 401);
            }

            // Chỉ owner và admin mới được xem
            if ($currentUser->role->name_role !== 'owner' && $currentUser->role->name_role !== 'admin') {
                return ApiResponse::error('Bạn không có quyền truy cập', null, 403);
            }

            $query = Book::with(['field.fieldCluster']);

            if ($currentUser->role->name_role === 'owner') {
                if (!$currentUser->ownerProfile) {
                    return ApiResponse::error('Bạn chưa có owner profile', null, 400);
                }

                $query->whereHas('field.fieldCluster', function ($q) use ($currentUser) {
                    $q->where('owner_id', $currentUser->ownerProfile->id);
                });
            }

            // 📊 Thống kê chi tiết theo từng sân
            $byField = (clone $query)
                ->selectRaw('field_id,
                    COUNT(*) as total_bookings,
                    SUM(CASE WHEN payment_status = "paid" THEN total_price ELSE 0 END) as revenue,
                    SUM(CASE WHEN payment_status = "pending" THEN total_price ELSE 0 END) as pending_revenue,
                    COUNT(CASE WHEN payment_status = "paid" THEN 1 END) as paid_count,
                    COUNT(CASE WHEN payment_status = "pending" THEN 1 END) as pending_count')
                ->groupBy('field_id')
                ->get()
                ->map(function ($item) {
                    $field = \App\Models\Field::find($item->field_id);
                    return [
                        'field_id' => $item->field_id,
                        'field_name' => $field ? $field->name : 'Unknown',
                        'total_bookings' => $item->total_bookings,
                        'paid_count' => $item->paid_count,
                        'pending_count' => $item->pending_count,
                        'revenue' => (float) $item->revenue,
                        'pending_revenue' => (float) $item->pending_revenue,
                    ];
                });

            // 📊 Thống kê theo loại booking
            $byBookingType = (clone $query)
                ->selectRaw('booking_type,
                    COUNT(*) as total_bookings,
                    SUM(CASE WHEN payment_status = "paid" THEN total_price ELSE 0 END) as revenue')
                ->groupBy('booking_type')
                ->get()
                ->keyBy('booking_type')
                ->map(function ($item) {
                    return [
                        'total_bookings' => $item->total_bookings,
                        'revenue' => (float) $item->revenue,
                    ];
                });

            $statistics = [
                'total_bookings' => $query->count(),
                'pending_payment' => (clone $query)->where('payment_status', 'pending')->count(),
                'pending_with_bill' => (clone $query)->where('payment_status', 'pending')->whereNotNull('payment_proof')->count(),
                'paid' => (clone $query)->where('payment_status', 'paid')->count(),
                'failed' => (clone $query)->where('payment_status', 'failed')->count(),
                'total_revenue' => (clone $query)->where('payment_status', 'paid')->sum('total_price'),
                'pending_revenue' => (clone $query)->where('payment_status', 'pending')->sum('total_price'),
                'by_payment_method' => [
                    'cash' => (clone $query)->where('payment_method', 'cash')->count(),
                    'transfer' => (clone $query)->where('payment_method', 'transfer')->count(),
                ],
                'by_field' => $byField,
                'by_booking_type' => $byBookingType,
            ];

            return ApiResponse::success('Thống kê thanh toán', $statistics, 200);
        } catch (Exception $e) {
            return ApiResponse::error('Lấy thống kê thất bại', $e->getMessage(), 500);
        }
    }

    /**
     * Lấy các time slots đã được book của một field trong một ngày cụ thể
     * API này giúp Frontend check available slots chính xác
     *
     * GET /api/fields/{field_id}/booked-slots?date=2026-04-28
     */
    public function getBookedSlots(Request $request, string $fieldId)
    {
        try {
            // Validate
            $validator = Validator::make(array_merge($request->all(), ['field_id' => $fieldId]), [
                'field_id' => ['required', 'integer', 'exists:fields,id'],
                'date' => ['required', 'date'],
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            $field = Field::with('fieldCluster')->find($fieldId);

            if (!$field) {
                return ApiResponse::error('Không tìm thấy sân', null, 404);
            }

            // Lấy TẤT CẢ bookings của field này trong ngày được chỉ định
            // Bao gồm cả booking thường và booking giải đấu
            $bookedSlots = Book::where('field_id', $fieldId)
                ->where('day_book', $request->date)
                ->whereNotIn('status', ['cancelled']) // Không tính booking đã hủy
                ->orderBy('start_time')
                ->get(['id', 'start_time', 'end_time', 'booking_type', 'tournament_id', 'status', 'payment_status']);

            return ApiResponse::success(
                'Danh sách time slots đã được đặt',
                [
                    'field_id' => (int) $fieldId,
                    'field_name' => $field->name,
                    'field_cluster' => $field->fieldCluster->name ?? null,
                    'date' => $request->date,
                    'total_booked_slots' => $bookedSlots->count(),
                    'booked_slots' => $bookedSlots->map(function ($booking) {
                        return [
                            'booking_id' => $booking->id,
                            'start_time' => substr($booking->start_time, 0, 5), // HH:MM
                            'end_time' => substr($booking->end_time, 0, 5),     // HH:MM
                            'booking_type' => $booking->booking_type,
                            'tournament_id' => $booking->tournament_id,
                            'status' => $booking->status,
                            'payment_status' => $booking->payment_status,
                        ];
                    }),
                ],
                200
            );
        } catch (Exception $e) {
            return ApiResponse::error('Lấy thông tin thất bại', $e->getMessage(), 500);
        }
    }

    /**
     * Check xem một time slot cụ thể có available không
     *
     * POST /api/fields/{field_id}/check-availability
     * Body: { "date": "2026-04-28", "start_time": "06:00", "end_time": "08:00" }
     */
    public function checkAvailability(Request $request, string $fieldId)
    {
        try {
            // Validate
            $validator = Validator::make(array_merge($request->all(), ['field_id' => $fieldId]), [
                'field_id' => ['required', 'integer', 'exists:fields,id'],
                'date' => ['required', 'date', 'after_or_equal:today'],
                'start_time' => ['required', 'date_format:H:i:s'],
                'end_time' => ['required', 'date_format:H:i:s', 'after:start_time'],
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            $field = Field::find($fieldId);

            if (!$field) {
                return ApiResponse::error('Không tìm thấy sân', null, 404);
            }

            // Kiểm tra sân có available không
            if ($field->status !== 'available') {
                return ApiResponse::success(
                    'Sân không khả dụng',
                    [
                        'is_available' => false,
                        'reason' => 'Sân đang trong trạng thái: ' . $field->status,
                    ],
                    200
                );
            }

            // Kiểm tra conflict với các booking hiện có
            $conflict = Book::where('field_id', $fieldId)
                ->where('day_book', $request->date)
                ->whereNotIn('status', ['cancelled']) // Không tính booking đã hủy
                ->where(function ($query) use ($request) {
                    $query->whereBetween('start_time', [$request->start_time, $request->end_time])
                        ->orWhereBetween('end_time', [$request->start_time, $request->end_time])
                        ->orWhere(function ($q) use ($request) {
                            $q->where('start_time', '<=', $request->start_time)
                                ->where('end_time', '>=', $request->end_time);
                        });
                })
                ->first();

            if ($conflict) {
                return ApiResponse::success(
                    'Time slot không khả dụng',
                    [
                        'is_available' => false,
                        'reason' => 'Khung giờ này đã được đặt',
                        'conflict_booking' => [
                            'id' => $conflict->id,
                            'start_time' => substr($conflict->start_time, 0, 5),
                            'end_time' => substr($conflict->end_time, 0, 5),
                            'booking_type' => $conflict->booking_type,
                        ],
                    ],
                    200
                );
            }

            return ApiResponse::success(
                'Time slot khả dụng',
                [
                    'is_available' => true,
                    'field_id' => (int) $fieldId,
                    'date' => $request->date,
                    'start_time' => $request->start_time,
                    'end_time' => $request->end_time,
                ],
                200
            );
        } catch (Exception $e) {
            return ApiResponse::error('Kiểm tra thất bại', $e->getMessage(), 500);
        }
    }
}
