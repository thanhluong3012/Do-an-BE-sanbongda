<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Field;
use App\Models\FieldCluster;
use App\Models\FieldHourlyPrice;
use App\Models\Notification;
use App\Models\Tournament;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class BookController extends Controller
{
    /**
     * Staff lấy danh sách booking theo phạm vi được giao.
     * API: GET /api/staff/bookings
     */
    public function staffBookings(Request $request)
    {
        try {
            $currentUser = auth('api')->user();

            if (!$currentUser) {
                return ApiResponse::error('Không xác thực được người dùng', null, 401);
            }

            if ($currentUser->role->name_role !== 'staff') {
                return ApiResponse::error('Chỉ nhân viên mới được truy cập API này', null, 403);
            }

            $staff = $currentUser->staff;
            if (!$staff) {
                return ApiResponse::error('Tài khoản staff chưa được liên kết hồ sơ nhân viên', null, 403);
            }

            $validator = Validator::make($request->all(), [
                'field_cluster_id' => ['nullable', 'integer', 'exists:field_clusters,id'],
                'status' => ['nullable', 'in:pending,confirmed,cancelled,completed'],
                'payment_status' => ['nullable', 'in:pending,paid,failed'],
                'booking_type' => ['nullable', 'in:regular,tournament'],
                'from_date' => ['nullable', 'date'],
                'to_date' => ['nullable', 'date'],
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            if ($request->filled('field_cluster_id')) {
                $isAllowedCluster = FieldCluster::where('id', $request->field_cluster_id)
                    ->where('owner_id', $staff->owner_id)
                    ->exists();

                if (!$isAllowedCluster) {
                    return ApiResponse::error('Bạn không có quyền xem booking của cụm sân này', null, 403);
                }
            }

            $query = Book::with(['user', 'field.fieldCluster', 'tournament'])
                ->whereHas('field.fieldCluster', function ($q) use ($request, $staff) {
                    $q->where('owner_id', $staff->owner_id);

                    if ($request->filled('field_cluster_id')) {
                        $q->where('id', $request->field_cluster_id);
                    }
                });

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }

            if ($request->filled('booking_type')) {
                $query->where('booking_type', $request->booking_type);
            }

            if ($request->filled('from_date')) {
                $query->whereDate('day_book', '>=', $request->from_date);
            }

            if ($request->filled('to_date')) {
                $query->whereDate('day_book', '<=', $request->to_date);
            }

            $books = $query->orderBy('day_book', 'desc')
                ->orderBy('start_time', 'desc')
                ->get();

            return ApiResponse::success('Danh sách booking theo phạm vi nhân viên', $books, 200);
        } catch (Exception $e) {
            return ApiResponse::error('Lấy danh sách booking thất bại', $e->getMessage(), 500);
        }
    }

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
                'total_price' => ['nullable', 'numeric', 'min:0'],
                'payment_method' => ['required', 'string', 'in:cash,vietqr'], // User chọn phương thức thanh toán
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            $field = Field::with('fieldCluster')->find($request->field_id);

            if (!$field) {
                return ApiResponse::error('Không tìm thấy sân', null, 404);
            }

            // Kiểm tra sân có available không
            if ($field->status !== 'available') {
                return ApiResponse::error('Sân không khả dụng để đặt', null, 400);
            }

            // Kiểm tra trùng lịch (bao gồm cả tournament bookings và regular bookings)
            // 🔥 CHỈ kiểm tra booking đang active (pending, confirmed, completed), bỏ qua booking đã cancelled
            $conflictBooking = Book::where('field_id', $request->field_id)
                ->where('day_book', $request->day_book)
                ->whereIn('status', ['pending', 'confirmed', 'completed'])
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

            // 🔥 TỰ ĐỘNG TÍNH GIÁ TỪ FIELD_HOURLY_PRICES
            // 1. Xác định day_type (weekday/weekend)
            $dayOfWeek = date('N', strtotime($request->day_book)); // 1=Monday, 7=Sunday
            $dayType = ($dayOfWeek >= 6) ? 'weekend' : 'weekday';

            // 2. Query giá từ database
            $priceConfig = FieldHourlyPrice::where('field_cluster_id', $field->field_cluster_id)
                ->where('field_type', $field->field_type)
                ->where('day_type', $dayType)
                ->first();

            // 3. Kiểm tra owner đã cấu hình giá chưa
            if (!$priceConfig) {
                return ApiResponse::errorWithData(
                    "Chưa cấu hình giá cho sân {$field->field_type} vào " .
                    ($dayType === 'weekend' ? 'cuối tuần' : 'ngày thường') .
                    ". Vui lòng liên hệ chủ sân hoặc chọn sân/ngày khác.",
                    [
                        'missing_price_config' => true,
                        'field_type' => $field->field_type,
                        'day_type' => $dayType,
                        'field_cluster_name' => $field->fieldCluster->name,
                        'contact_phone' => $field->fieldCluster->phone,
                    ],
                    400
                );
            }

            // 4. Tính giá tự động
            $startTime = new \DateTime($request->start_time);
            $endTime = new \DateTime($request->end_time);
            $hours = ($endTime->getTimestamp() - $startTime->getTimestamp()) / 3600;
            $calculatedPrice = round($priceConfig->price_hour * $hours, 2);

            // 5. Validate nếu FE gửi total_price (để phát hiện price change giữa lúc FE load và submit)
            if ($request->filled('total_price')) {
                $tolerance = 0.01; // Cho phép sai số 0.01đ do làm tròn
                if (abs($request->total_price - $calculatedPrice) > $tolerance) {
                    return ApiResponse::errorWithData(
                        'Giá không khớp với hệ thống. Có thể chủ sân vừa thay đổi giá. Vui lòng tải lại trang.',
                        [
                            'price_mismatch' => true,
                            'expected_price' => $calculatedPrice,
                            'received_price' => $request->total_price,
                            'price_hour' => $priceConfig->price_hour,
                            'hours' => $hours,
                        ],
                        400
                    );
                }
            }

            Log::info('💰 [BOOKING] Auto-calculated price', [
                'field_id' => $field->id,
                'field_type' => $field->field_type,
                'day_type' => $dayType,
                'price_hour' => $priceConfig->price_hour,
                'hours' => $hours,
                'total_price' => $calculatedPrice,
            ]);

            $book = Book::create([
                'user_id' => $currentUser->id,
                'booking_type' => 'regular', // 🔥 CHỜ OWNER XÁC NHẬN ĐẶT SÂN
                'field_id' => $request->field_id,
                'day_book' => $request->day_book,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'total_price' => $calculatedPrice,  // 🔥 Backend tự động tính, không dùng giá từ FE
                'payment_status' => 'pending',  // 🔥 Trạng thái thanh toán (chưa thanh toán)
                'payment_method' => $request->payment_method,  // 🔥 User chọn SAU KHI owner xác nhận booking
            ]);

            // Load relationships
            $book->load(['user', 'field.fieldCluster.owner']);

            // Lấy field cluster để dùng trong notifications và payment info
            $fieldCluster = $book->field->fieldCluster;

            // 🔔 Tạo thông báo cho user
            try {
                Notification::create([
                    'user_id' => $currentUser->id,
                    'type' => 'booking_created',
                    'title' => '⏳ Đơn đặt sân đang chờ xác nhận',
                    'message' => "Bạn đã đặt sân {$book->field->fieldCluster->name} vào ngày {$book->day_book->format('d/m/Y')} ({$book->start_time} - {$book->end_time}). Vui lòng chờ chủ sân xác nhận đặt sân.",
                    'data' => [
                        'booking_id' => $book->id,
                        'field_name' => $book->field->name ?? '',
                        'field_cluster_name' => $book->field->fieldCluster->name,
                        'day_book' => $book->day_book->format('Y-m-d'),
                        'start_time' => $book->start_time,
                        'end_time' => $book->end_time,
                        'payment_method' => $request->payment_method,  // Phương thức thanh toán
                        'payment_status' => 'pending',
                        'booking_status' => 'pending',
                        'total_price' => $calculatedPrice,
                        'action_required' => 'wait_confirmation',
                    ],
                ]);
            } catch (Exception $notifError) {
                Log::warning('Failed to create booking notification: ' . $notifError->getMessage());
            }

            // 🔔 Tạo thông báo cho owner để xác nhận đặt sân
            try {
                if ($fieldCluster->owner && $fieldCluster->owner->user) {
                    $paymentMethodLabel = match($request->payment_method) {
                        'cash' => 'Tiền mặt',
                        'transfer' => 'Chuyển khoản',
                        'vnpay' => 'VNPay QR',
                        default => $request->payment_method
                    };

                    $userContact = $currentUser->phone ?? $currentUser->email;

                    Notification::create([
                        'user_id' => $fieldCluster->owner->user->id,
                        'type' => 'booking_pending_confirmation',
                        'title' => '📋 Có đơn đặt sân mới cần xác nhận',
                        'message' => "User {$currentUser->name} ({$userContact}) đã đặt sân tại {$fieldCluster->name} vào ngày {$book->day_book->format('d/m/Y')} ({$book->start_time} - {$book->end_time}). Số tiền: " . number_format($calculatedPrice) . " VNĐ. Hình thức thanh toán: {$paymentMethodLabel}. Vui lòng xác nhận hoặc từ chối đơn đặt sân.",
                        'data' => [
                            'booking_id' => $book->id,
                            'user_name' => $currentUser->name,
                            'user_email' => $currentUser->email,
                            'user_phone' => $currentUser->phone ?? null,
                            'field_cluster_name' => $fieldCluster->name,
                            'day_book' => $book->day_book->format('Y-m-d'),
                            'start_time' => $book->start_time,
                            'end_time' => $book->end_time,
                            'total_price' => $calculatedPrice,
                            'payment_method' => $request->payment_method,
                            'payment_method_label' => $paymentMethodLabel,
                            'action_required' => 'confirm_booking',
                        ],
                    ]);
                }
            } catch (Exception $notifError) {
                Log::warning('Failed to create owner notification: ' . $notifError->getMessage());
            }

            // 🔥 KHÔNG trả về payment info vì user chưa chọn payment_method
            // User sẽ chọn payment_method SAU KHI owner xác nhận booking

            return ApiResponse::success(
                'Đặt sân thành công! Vui lòng chờ chủ sân xác nhận đơn đặt sân của bạn.',
                [
                    'booking' => $book,
                    'status' => 'pending',
                    'message' => 'Đơn đặt sân đang chờ chủ sân xác nhận. Bạn sẽ nhận được thông báo khi đơn được xử lý.',
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
                    'payment_method_label' => match($book->payment_method) {
                        'cash' => 'Tiền mặt',
                        'transfer' => 'Chuyển khoản',
                        'vnpay' => 'VNPay QR',
                        default => $book->payment_method
                    },
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
     * Owner/Staff xác nhận hoặc từ chối đơn đặt sân
     * API mới: POST /api/books/{id}/confirm-booking
     * Body: { "action": "approve|reject", "note": "..." }
     */
    public function confirmBooking(Request $request, string $id)
    {
        try {
            Log::info('🔍 [BOOKING CONFIRM] API được gọi', [
                'booking_id' => $id,
                'action' => $request->action,
            ]);

            $book = Book::with(['user', 'field.fieldCluster.owner.user'])->find($id);

            if (!$book) {
                return ApiResponse::error('Không tìm thấy booking', null, 404);
            }

            $currentUser = auth('api')->user();

            if (!$currentUser) {
                return ApiResponse::error('Không xác thực được người dùng', null, 401);
            }

            // Chỉ owner của sân hoặc admin hoặc staff mới được xác nhận
            if ($currentUser->role->name_role !== 'admin') {
                if ($currentUser->role->name_role === 'owner') {
                    if (!$currentUser->ownerProfile || $book->field->fieldCluster->owner_id !== $currentUser->ownerProfile->id) {
                        return ApiResponse::error('Bạn không có quyền xác nhận đơn đặt sân này', null, 403);
                    }
                } elseif ($currentUser->role->name_role === 'staff') {
                    // Staff cần thuộc cụm sân đó
                    $staff = $currentUser->staff;
                    if (!$staff || $staff->field_cluster_id !== $book->field->field_cluster_id) {
                        return ApiResponse::error('Bạn không có quyền xác nhận đơn đặt sân này', null, 403);
                    }
                } else {
                    return ApiResponse::error('Bạn không có quyền xác nhận đơn đặt sân', null, 403);
                }
            }

            // Kiểm tra booking đang ở trạng thái pending
            if ($book->status !== 'pending') {
                return ApiResponse::error('Đơn đặt sân này không ở trạng thái chờ xác nhận', null, 400);
            }

            $validator = Validator::make($request->all(), [
                'action' => ['required', 'in:approve,reject'],
                'note' => ['nullable', 'string', 'max:500'],
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            if ($request->action === 'approve') {
                // ✅ XÁC NHẬN ĐẶT SÂN
                $book->status = 'confirmed';
                $book->confirmed_by_user_id = $currentUser->id;
                $book->save();

                $message = 'Đã xác nhận đơn đặt sân. User có thể thanh toán.';

                // 🔔 Thông báo cho user
                try {
                    $fieldCluster = $book->field->fieldCluster;
                    $paymentMethodLabel = match($book->payment_method) {
                        'cash' => 'Tiền mặt',
                        'transfer' => 'Chuyển khoản',
                        'vnpay' => 'VNPay QR',
                        default => $book->payment_method
                    };
                    $actionMessage = match($book->payment_method) {
                        'cash' => 'Vui lòng thanh toán tiền mặt khi đến sân.',
                        'transfer' => 'Vui lòng chuyển khoản và upload bill thanh toán.',
                        'vnpay' => 'Vui lòng quét mã QR VNPay để thanh toán.',
                        default => 'Vui lòng thanh toán theo phương thức đã chọn.'
                    };

                    Notification::create([
                        'user_id' => $book->user_id,
                        'type' => 'booking_confirmed',
                        'title' => '✅ Đơn đặt sân đã được xác nhận',
                        'message' => "Đơn đặt sân tại {$fieldCluster->name} vào ngày {$book->day_book->format('d/m/Y')} ({$book->start_time} - {$book->end_time}) đã được chủ sân xác nhận. Hình thức thanh toán: {$paymentMethodLabel}. {$actionMessage}",
                        'data' => [
                            'booking_id' => $book->id,
                            'field_cluster_name' => $fieldCluster->name,
                            'day_book' => $book->day_book->format('Y-m-d'),
                            'start_time' => $book->start_time,
                            'end_time' => $book->end_time,
                            'total_price' => $book->total_price,
                            'payment_method' => $book->payment_method,
                            'payment_method_label' => $paymentMethodLabel,
                            'action_required' => $book->payment_method === 'transfer' ? 'proceed_payment' : 'pay_at_venue',
                        ],
                    ]);
                } catch (Exception $notifError) {
                    Log::warning('Failed to create booking confirmation notification: ' . $notifError->getMessage());
                }

            } else {
                // ❌ TỪ CHỐI ĐẶT SÂN
                $book->status = 'cancelled';
                $book->confirmed_by_user_id = $currentUser->id;
                $book->save();

                $message = 'Đã từ chối đơn đặt sân. Khung giờ đã được giải phóng.';

                Log::info('🔥 [BOOKING REJECT] Booking rejected, slot released', [
                    'booking_id' => $book->id,
                    'status' => 'cancelled',
                ]);

                // 🔔 Thông báo cho user
                try {
                    Notification::create([
                        'user_id' => $book->user_id,
                        'type' => 'booking_rejected',
                        'title' => '❌ Đơn đặt sân bị từ chối',
                        'message' => "Đơn đặt sân tại {$book->field->fieldCluster->name} vào ngày {$book->day_book->format('d/m/Y')} đã bị từ chối. Lý do: " . ($request->note ?? 'Không có lý do cụ thể'),
                        'data' => [
                            'booking_id' => $book->id,
                            'field_cluster_name' => $book->field->fieldCluster->name,
                            'rejected_reason' => $request->note ?? null,
                        ],
                    ]);
                } catch (Exception $notifError) {
                    Log::warning('Failed to create booking rejection notification: ' . $notifError->getMessage());
                }
            }

            // Load lại booking với đầy đủ thông tin
            $book->load(['user', 'field.fieldCluster']);

            return ApiResponse::success(
                $message,
                [
                    'booking_id' => $book->id,
                    'status' => $book->status,
                    'processed_at' => $book->updated_at,
                    'confirmed_by' => $currentUser->name,
                    'note' => $request->note ?? null,
                    'booking_info' => [
                        'field_cluster_name' => $book->field->fieldCluster->name,
                        'day_book' => $book->day_book->format('d/m/Y'),
                        'time_slot' => "{$book->start_time} - {$book->end_time}",
                        'total_price' => number_format($book->total_price) . ' VNĐ',
                        'payment_method' => $book->payment_method === 'cash' ? 'Tiền mặt' : 'Chuyển khoản',
                        'customer_name' => $book->user->name,
                        'customer_phone' => $book->user->phone ?? null,
                        'customer_email' => $book->user->email,
                    ],
                ],
                200
            );
        } catch (Exception $e) {
            return ApiResponse::error('Xác nhận thất bại', $e->getMessage(), 500);
        }
    }

    /**
     * User thay đổi phương thức thanh toán (nếu chưa thanh toán)
     * API: POST /api/books/{id}/select-payment-method
     * Body: { "payment_method": "cash|vietqr" }
     *
     * ⚠️ LƯU Ý: API này cho phép user THAY ĐỔI payment_method sau khi đã chọn lúc tạo booking
     * Chỉ cho phép thay đổi khi chưa thanh toán (payment_status = 'pending')
     */
    public function selectPaymentMethod(Request $request, string $id)
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

            // Chỉ user sở hữu booking mới được thay đổi payment_method
            if ($book->user_id !== $currentUser->id) {
                return ApiResponse::error('Bạn không có quyền thay đổi phương thức thanh toán cho booking này', null, 403);
            }

            // Chỉ cho phép thay đổi nếu chưa thanh toán
            if ($book->payment_status !== 'pending') {
                return ApiResponse::error('Không thể thay đổi phương thức thanh toán vì booking đã ' . ($book->payment_status === 'paid' ? 'thanh toán' : 'bị từ chối thanh toán'), null, 400);
            }

            $validator = Validator::make($request->all(), [
                'payment_method' => ['required', 'in:cash,vietqr'],
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            // Lưu payment_method cũ để so sánh
            $oldPaymentMethod = $book->payment_method;

            // Cập nhật payment_method
            $book->payment_method = $request->payment_method;

            // Nếu đổi từ vietqr sang cash hoặc ngược lại, xóa bill cũ
            if ($oldPaymentMethod !== $request->payment_method && $book->payment_proof) {
                $book->payment_proof = null;
            }

            $book->save();

            // Chuẩn bị response với thông tin thanh toán
            $fieldCluster = $book->field->fieldCluster;
            $paymentInfo = [
                'booking_id' => $book->id,
                'payment_method' => $book->payment_method,
                'total_price' => $book->total_price,
            ];

            if ($request->payment_method === 'vietqr') {
                // Trả về QR code để thanh toán
                if ($fieldCluster->owner && $fieldCluster->owner->qr_code_image) {
                    $paymentInfo['qr_code_url'] = $fieldCluster->owner->qr_code_image;
                    $paymentInfo['instruction'] = "Vui lòng quét mã QR để chuyển khoản ĐÚNG số tiền: " . number_format($book->total_price) . " VNĐ và ghi nội dung: BOOK-{$book->id}";
                    $paymentInfo['vietqr_note'] = "BOOK-{$book->id}";
                    $paymentInfo['next_step'] = 'upload_bill';
                    $paymentInfo['message'] = 'Vui lòng chuyển khoản và upload bill để chủ sân xác nhận.';
                } else {
                    $paymentInfo['message'] = 'Chủ sân chưa cập nhật QR code. Vui lòng liên hệ chủ sân để được hướng dẫn thanh toán.';
                }
            } else {
                // Thanh toán tiền mặt
                $paymentInfo['instruction'] = "Vui lòng thanh toán tiền mặt tại sân. Số tiền: " . number_format($book->total_price) . " VNĐ";
                $paymentInfo['next_step'] = 'pay_at_venue';
                $paymentInfo['message'] = 'Vui lòng đến sân và thanh toán tiền mặt.';
            }

            $paymentInfo['owner_contact'] = [
                'name' => $fieldCluster->name,
                'phone' => $fieldCluster->phone,
                'address' => $fieldCluster->address,
            ];

            $message = $oldPaymentMethod !== $request->payment_method
                ? 'Đã thay đổi phương thức thanh toán thành: ' . ($request->payment_method === 'cash' ? 'Tiền mặt' : 'Chuyển khoản')
                : 'Phương thức thanh toán: ' . ($request->payment_method === 'cash' ? 'Tiền mặt' : 'Chuyển khoản');

            return ApiResponse::success(
                $message,
                $paymentInfo,
                200
            );
        } catch (Exception $e) {
            return ApiResponse::error('Thao tác thất bại', $e->getMessage(), 500);
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

            // 🔥 Kiểm tra booking đã được xác nhận chưa
            if ($book->status !== 'confirmed') {
                return ApiResponse::error('Đơn đặt sân chưa được chủ sân xác nhận. Vui lòng chờ xác nhận trước khi thanh toán.', null, 400);
            }

            // 🔥 Kiểm tra đã chọn payment_method chưa
            if ($book->payment_method === null) {
                return ApiResponse::error('Bạn chưa chọn phương thức thanh toán. Vui lòng chọn phương thức thanh toán trước.', null, 400);
            }

            // Chỉ booking chọn vietqr mới cần upload bill
            if ($book->payment_method !== 'vietqr') {
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
                            'day_book' => $book->day_book->format('Y-m-d'),
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
            Log::info('🔍 [BOOK CONFIRM] API /books/{id}/confirm-payment ĐƯỢC GỌI', [
                'booking_id' => $id,
                'action' => $request->action,
                'note' => $request->note,
            ]);

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

            // 🔥 Kiểm tra booking đã được xác nhận đặt sân chưa
            if ($book->status !== 'confirmed') {
                return ApiResponse::error('Đơn đặt sân chưa được xác nhận. Chỉ có thể xác nhận thanh toán sau khi đơn đặt sân được approve.', null, 400);
            }

            // 🔥 Kiểm tra user đã chọn payment_method chưa
            if ($book->payment_method === null) {
                return ApiResponse::error('User chưa chọn phương thức thanh toán.', null, 400);
            }

            // Kiểm tra booking đang ở trạng thái pending payment
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

            // Nếu là vietqr, kiểm tra đã upload bill chưa
            if ($book->payment_method === 'vietqr' && !$book->payment_proof && $request->action === 'approve') {
                return ApiResponse::error('User chưa upload bill chuyển khoản. Không thể xác nhận thanh toán.', null, 400);
            }

            // Update payment status
            if ($request->action === 'approve') {
                $book->payment_status = 'paid';
                $book->status = 'completed';  // 🔥 ĐÃ HOÀN TẤT (confirmed + paid)
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
                            'day_book' => $book->day_book->format('Y-m-d'),
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
                // 🔥 GIỮ NGUYÊN status='confirmed' vì đã xác nhận đặt sân, chỉ từ chối thanh toán
                // Nếu muốn hủy booking thì owner dùng API khác

                Log::info('🔥 [PAYMENT REJECT] Payment rejected but booking still confirmed', [
                    'booking_id' => $book->id,
                    'tournament_id' => $book->tournament_id,
                    'status' => 'cancelled',
                    'payment_status' => 'failed',
                ]);

                // Xóa file bill bị từ chối khỏi storage
                if ($book->payment_proof) {
                    $oldPath = public_path('storage/' . $book->payment_proof);
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }
                $book->payment_proof = null;  // User phải upload lại
                $message = 'Đã từ chối thanh toán. Booking đã bị hủy nhưng vẫn lưu lịch sử.';

                // ✅ Tạo thông báo từ chối
                try {
                    Notification::create([
                        'user_id' => $book->user_id,
                        'type' => 'booking_payment_rejected',
                        'title' => '❌ Thanh toán đặt sân bị từ chối',
                        'message' => "Thanh toán cho đặt sân tại {$book->field->fieldCluster->name} vào ngày {$book->day_book->format('d/m/Y')} đã bị từ chối. Lý do: " . ($request->note ?? 'Không có lý do cụ thể') . ($book->tournament_id ? ' Booking này thuộc giải đấu.' : ''),
                        'data' => [
                            'booking_id' => $book->id,
                            'field_name' => $book->field->name ?? '',
                            'field_cluster_name' => $book->field->fieldCluster->name,
                            'payment_method' => $book->payment_method,
                            'rejected_reason' => $request->note ?? null,
                            'tournament_id' => $book->tournament_id,
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
            if ($book->payment_method === 'vietqr' && $book->payment_status === 'pending') {
                if ($fieldCluster->owner && $fieldCluster->owner->qr_code_image) {
                    $paymentInfo['qr_code_url'] = $fieldCluster->owner->qr_code_image;
                    $paymentInfo['instruction'] = "Vui lòng quét mã QR để chuyển khoản ĐÚNG số tiền: " . number_format($book->total_price) . " VNĐ và ghi nội dung: BOOK-{$book->id}";
                    $paymentInfo['vietqr_note'] = "BOOK-{$book->id}";
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
     * - vietqr: chỉ hiện booking đã upload bill
     * - cash: hiện tất cả booking cash đang pending
     * Query params: payment_method (cash|vietqr), booking_type (regular|tournament)
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
                    // Lấy vietqr booking đã upload bill VÀ cash booking (không cần bill)
                    $q->where(function ($q2) {
                        $q2->where('payment_method', 'vietqr')->whereNotNull('payment_proof');
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
                    'vietqr_pending' => $books->where('payment_method', 'vietqr')->count(),
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
                    'vietqr' => (clone $query)->where('payment_method', 'vietqr')->count(),
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
