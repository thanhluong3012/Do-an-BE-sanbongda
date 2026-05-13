<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\OwnerController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\OwnerRegistrationController;
use App\Http\Controllers\Api\FieldHourlyPriceController;
use App\Http\Controllers\Api\FieldClusterController;
use App\Http\Controllers\Api\FieldController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\TeamMemberController;
use App\Http\Controllers\Api\TournamentController;
use App\Http\Controllers\Api\TournamentRegistrationController;
use App\Http\Controllers\Api\TournamentMatchController;
use App\Http\Controllers\Api\TournamentRoundController;
use App\Http\Controllers\Api\TournamentGroupController;
use App\Http\Controllers\Api\TournamentSessionPriceController;
use App\Http\Controllers\Api\NotificationController;


// ==========================================
// ROUTES CÔNG KHAI (không cần đăng nhập)
// ==========================================

// Authentication Routes
Route::prefix('auth')->group(function () {
    // Đăng ký: POST /api/auth/register
    Route::post('register', [AuthController::class, 'register']);

    // Đăng nhập: POST /api/auth/login
    Route::post('login', [AuthController::class, 'login']);

    // Xác nhận email: GET /api/auth/verify-email/{token}
    Route::get('verify-email/{token}', [AuthController::class, 'verifyEmail']);
});

// Đăng ký làm chủ sân - Public (không cần đăng nhập)
Route::post('owner-registrations', [OwnerRegistrationController::class, 'store']);

// Public Routes - Xem thông tin sân cho khách (không cần đăng nhập)
Route::prefix('public')->group(function () {
    // Cụm sân
    Route::get('field-clusters', [FieldClusterController::class, 'publicIndex']);
    Route::get('field-clusters/{id}', [FieldClusterController::class, 'publicShow']);

    // Sân
    Route::get('fields', [FieldController::class, 'publicIndex']);
    Route::get('fields/{id}', [FieldController::class, 'publicShow']);

    // Giá sân theo giờ
    Route::get('field-hourly-prices', [FieldHourlyPriceController::class, 'publicIndex']);
    Route::get('field-hourly-prices/{id}', [FieldHourlyPriceController::class, 'publicShow']);

    // 🔥 Giải đấu công khai (prefix 'public' để tránh conflict với authenticated routes)
    Route::get('public/tournaments', [TournamentController::class, 'publicIndex']);
    Route::get('public/tournaments/{id}', [TournamentController::class, 'publicShow']);

    // Xem lịch thi đấu/bracket (có logic kiểm tra quyền bên trong controller)
    Route::get('tournaments/{id}/bracket', [TournamentController::class, 'getBracket']);
});


// ==========================================
// ROUTES CẦN XÁC THỰC (jwt.auth middleware)
// ==========================================

Route::middleware('jwt.auth')->group(function () {

    // ==========================================
    // Auth Routes - Dành cho user đã đăng nhập
    // ==========================================
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // ==========================================
    // Profile - TẤT CẢ USER ĐÃ ĐĂNG NHẬP
    // ==========================================
    Route::put('profile', [AuthController::class, 'updateProfile']);

    // ==========================================
    // Notifications - TẤT CẢ USER ĐÃ ĐĂNG NHẬP
    // ==========================================
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']); // Danh sách thông báo
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']); // Đếm chưa đọc
        Route::put('/{id}/read', [NotificationController::class, 'markAsRead']); // Đánh dấu 1 cái đã đọc
        Route::put('/mark-all-read', [NotificationController::class, 'markAllAsRead']); // Đánh dấu tất cả đã đọc
        Route::delete('/{id}', [NotificationController::class, 'destroy']); // Xóa 1 thông báo
        Route::delete('/read/all', [NotificationController::class, 'deleteAllRead']); // Xóa tất cả đã đọc
    });

    // ==========================================
    // User & Role Management - CHỈ ADMIN
    // ==========================================
    Route::middleware('check.role:admin')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::apiResource('roles', RoleController::class);
    });

    // ==========================================
    // Owner Registration Management - CHỈ ADMIN
    // ==========================================
    Route::prefix('admin')->middleware('check.role:admin')->group(function () {
        Route::get('owner-registrations', [OwnerRegistrationController::class, 'index']);
        Route::get('owner-registrations/{id}', [OwnerRegistrationController::class, 'show']);
        Route::put('owner-registrations/{id}/approve', [OwnerRegistrationController::class, 'approve']);
        Route::put('owner-registrations/{id}/reject', [OwnerRegistrationController::class, 'reject']);
        Route::delete('owner-registrations/{id}', [OwnerRegistrationController::class, 'destroy']);
    });

    // ==========================================
    // Owner & Staff Management - ADMIN & OWNER ONLY
    // ==========================================
    Route::middleware('check.role:admin,owner')->group(function () {
        // Quản lý chủ sân
        Route::apiResource('owners', OwnerController::class);

        // Owner Profile Management - Owner tự quản lý thông tin của mình
        Route::put('owner/profile', [OwnerController::class, 'updateOwnerProfile']);
        Route::post('owner/upload-qr', [OwnerController::class, 'uploadQRCode']);
        Route::get('owner/qr-code', [OwnerController::class, 'getQRCode']);

        // Quản lý nhân viên
        Route::apiResource('staffs', StaffController::class);

        // QUẢN LÝ Cụm sân (Tạo, Sửa, Xóa)
        Route::post('field-clusters', [FieldClusterController::class, 'store']);
        Route::put('field-clusters/{id}', [FieldClusterController::class, 'update']);
        Route::delete('field-clusters/{id}', [FieldClusterController::class, 'destroy']);

        // QUẢN LÝ Sân (Tạo, Sửa, Xóa)
        Route::post('fields', [FieldController::class, 'store']);
        Route::put('fields/{id}', [FieldController::class, 'update']);
        Route::delete('fields/{id}', [FieldController::class, 'destroy']);

        // QUẢN LÝ Giá theo giờ (Tạo, Sửa, Xóa)
        Route::post('field-hourly-prices', [FieldHourlyPriceController::class, 'store']);
        Route::put('field-hourly-prices/{id}', [FieldHourlyPriceController::class, 'update']);
        Route::delete('field-hourly-prices/{id}', [FieldHourlyPriceController::class, 'destroy']);

        // QUẢN LÝ Giá giải đấu (Tạo, Sửa, Xóa)
        Route::apiResource('tournament-session-prices', TournamentSessionPriceController::class)->except(['show']);
    });

    // ==========================================
    // Field Information - TẤT CẢ USER ĐÃ ĐĂNG NHẬP (Chỉ xem)
    // ==========================================
    // Xem cụm sân
    Route::get('field-clusters', [FieldClusterController::class, 'index']);
    Route::get('field-clusters/{id}', [FieldClusterController::class, 'show']);

    // Xem sân
    Route::get('fields', [FieldController::class, 'index']);
    Route::get('fields/{id}', [FieldController::class, 'show']);

    // Xem giá theo giờ
    Route::get('field-hourly-prices', [FieldHourlyPriceController::class, 'index']);
    Route::get('field-hourly-prices/{id}', [FieldHourlyPriceController::class, 'show']);

    // Xem giá giải đấu
    Route::get('tournament-session-prices', [TournamentSessionPriceController::class, 'index']);
    Route::get('tournament-session-prices/{id}', [TournamentSessionPriceController::class, 'show']);

    // ==========================================
    // Booking (Đặt sân) - Tất cả user đã đăng nhập
    // ==========================================
    Route::apiResource('books', BookController::class);

    // Lấy thông tin thanh toán (bao gồm QR code)
    Route::get('books/{id}/payment-info', [BookController::class, 'getPaymentInfo']);

    // 🔥 Owner/Staff xác nhận hoặc từ chối đặt sân
    Route::post('books/{id}/confirm-booking', [BookController::class, 'confirmBooking']);

    // 🔥 User chọn phương thức thanh toán SAU KHI owner xác nhận
    Route::post('books/{id}/select-payment-method', [BookController::class, 'selectPaymentMethod']);

    // Upload bill chuyển khoản cho booking
    Route::post('books/{id}/upload-payment-proof', [BookController::class, 'uploadPaymentProof']);

    // Owner/Admin xác nhận thanh toán
    Route::put('books/{id}/confirm-payment', [BookController::class, 'confirmPayment']);

    // Lấy các time slots đã được book của một field trong một ngày (PUBLIC - không cần auth)
    Route::get('fields/{field_id}/booked-slots', [BookController::class, 'getBookedSlots']);

    // Check availability của một time slot cụ thể (PUBLIC - không cần auth)
    Route::post('fields/{field_id}/check-availability', [BookController::class, 'checkAvailability']);

    // ==========================================
    // Quản lý thanh toán (Owner/Admin)
    // ==========================================
    Route::middleware('check.role:owner,admin')->group(function () {
        // Danh sách booking chờ xác nhận thanh toán
        Route::get('bookings/pending-payments', [BookController::class, 'getPendingPayments']);

        // Thống kê thanh toán
        Route::get('bookings/payment-statistics', [BookController::class, 'getPaymentStatistics']);
    });

    // ==========================================
    // Team (Đội bóng) - Tất cả user đã đăng nhập
    // ==========================================
    Route::apiResource('teams', TeamController::class);

    // Quản lý thành viên đội
    Route::prefix('teams/{team_id}')->group(function () {
        Route::get('members', [TeamMemberController::class, 'index']);
        Route::post('members', [TeamMemberController::class, 'store']);
        Route::get('members/{id}', [TeamMemberController::class, 'show']);
        Route::put('members/{id}', [TeamMemberController::class, 'update']);
        Route::delete('members/{id}', [TeamMemberController::class, 'destroy']);
    });

    // ==========================================
    // Tournament (Giải đấu) - Cần đăng nhập để tạo/sửa/xóa
    // ==========================================
    // Xem danh sách và chi tiết không cần auth (đã có ở public routes)
    Route::get('tournaments', [TournamentController::class, 'index']); // User đã login xem theo quyền
    Route::post('tournaments', [TournamentController::class, 'store']);
    Route::get('tournaments/{tournament}', [TournamentController::class, 'show']); // User đã login xem theo quyền
    Route::put('tournaments/{tournament}', [TournamentController::class, 'update']);

    // ==========================================
    // Booking sân cho giải đấu
    // ==========================================
    Route::post('tournaments/{id}/book-fields', [TournamentController::class, 'bookFields']);
    Route::get('tournaments/{id}/field-bookings', [TournamentController::class, 'getFieldBookings']);
    Route::delete('tournaments/{id}/field-bookings/{booking_id}', [TournamentController::class, 'deleteFieldBooking']);

    // Owner xác nhận tất cả booking của giải (giống như confirm booking thường)
    Route::put('tournaments/{id}/confirm-bookings', [TournamentController::class, 'confirmTournamentBookings']);

    Route::delete('tournaments/{tournament}', [TournamentController::class, 'destroy']);

    // ==========================================
    // Payment Management - Thanh toán giải đấu
    // ==========================================
    // User upload bill chuyển khoản
    Route::post('tournaments/{id}/upload-payment-proof', [TournamentController::class, 'uploadPaymentProof']);

    // Owner xác nhận thanh toán (approve/reject)
    Route::put('tournaments/{id}/confirm-payment', [TournamentController::class, 'confirmPayment']);

    // ==========================================
    // Tournament Rules File - File thể lệ giải đấu
    // ==========================================
    // Upload file thể lệ (.pdf, .doc, .docx)
    Route::post('tournaments/{id}/upload-rules-file', [TournamentController::class, 'uploadRulesFile']);

    // Download file thể lệ (public - không cần đăng nhập)
    Route::get('tournaments/{id}/download-rules-file', [TournamentController::class, 'downloadRulesFile'])->withoutMiddleware('auth:api');

    // Xóa file thể lệ
    Route::delete('tournaments/{id}/delete-rules-file', [TournamentController::class, 'deleteRulesFile']);

    // Routes đặc biệt cho giải đấu
    Route::post('tournaments/{id}/publish', [TournamentController::class, 'publish']);

    // ==========================================
    // Lịch thi đấu & Bracket
    // ==========================================
    // Tự động sinh lịch (1 nút bấm)
    Route::post('tournaments/{id}/generate-schedule', [TournamentController::class, 'generateSchedule']);
    // Lấy bracket/lịch để FE render sơ đồ (authenticated users)
    Route::get('tournaments/{id}/bracket', [TournamentController::class, 'getBracket']);
    // Công bố lịch thi đấu ra public
    Route::post('tournaments/{id}/publish-schedule', [TournamentController::class, 'publishSchedule']);
    // Ẩn lịch thi đấu (chỉ người tạo giải xem)
    Route::post('tournaments/{id}/unpublish-schedule', [TournamentController::class, 'unpublishSchedule']);
    // Xóa toàn bộ lịch để tạo lại
    Route::delete('tournaments/{id}/schedule', [TournamentController::class, 'resetSchedule']);

    // ==========================================
    // Tournament Registration (Đăng ký giải đấu) - Tất cả user đã đăng nhập
    // ==========================================
    Route::apiResource('tournament-registrations', TournamentRegistrationController::class);

    // Routes đặc biệt cho đăng ký giải đấu
    Route::put('tournament-registrations/{id}/approve', [TournamentRegistrationController::class, 'approve']);
    Route::put('tournament-registrations/{id}/reject', [TournamentRegistrationController::class, 'reject']);

    // 🔥 Payment routes cho giải đấu (giống booking sân)
    Route::post('tournament-registrations/{id}/upload-payment-proof', [TournamentRegistrationController::class, 'uploadPaymentProof']);
    Route::put('tournament-registrations/{id}/confirm-payment', [TournamentRegistrationController::class, 'confirmPayment']);
    Route::get('tournament-registrations/{id}/payment-info', [TournamentRegistrationController::class, 'getPaymentInfo']);

    // ==========================================
    // Tournament Match (Trận đấu) - Tất cả user đã đăng nhập
    // ==========================================
    Route::apiResource('tournament-matches', TournamentMatchController::class);

    // Routes đặc biệt cho trận đấu
    Route::put('tournament-matches/{id}/update-score', [TournamentMatchController::class, 'updateScore']);
    // Kéo thả gán đội vào slot home/away (FE drag & drop)
    Route::patch('tournament-matches/{id}/assign-team', [TournamentMatchController::class, 'assignTeam']);

    // ==========================================
    // Tournament Round (Vòng đấu) - quản lý thủ công
    // ==========================================
    Route::apiResource('tournament-rounds', TournamentRoundController::class);

    // ==========================================
    // Tournament Group (Bảng đấu) - quản lý thủ công
    // ==========================================
    Route::apiResource('tournament-groups', TournamentGroupController::class)->except(['update']);
});
