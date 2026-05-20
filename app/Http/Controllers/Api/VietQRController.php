<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Services\VietQRService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VietQRController extends Controller
{
    protected $vietqrService;

    public function __construct(VietQRService $vietqrService)
    {
        $this->vietqrService = $vietqrService;
    }

    /**
     * 🚀 Tạo VietQR Code để thanh toán
     *
     * POST /api/vietqr/create-payment/{bookingId}
     */
    public function createPayment(Request $request, $bookingId)
    {
        try {
            $user = auth('api')->user();
            // 🔥 Load thêm owner thông qua fieldCluster
            $booking = Book::with('field.fieldCluster.owner')->find($bookingId);

            // Validate booking
            if (!$booking) {
                return ApiResponse::error('Không tìm thấy booking', null, 404);
            }

            if ($booking->user_id !== $user->id) {
                return ApiResponse::error('Bạn không có quyền thanh toán booking này', null, 403);
            }

            if ($booking->payment_status === 'paid') {
                return ApiResponse::error('Booking đã được thanh toán', null, 400);
            }

            if ($booking->status === 'cancelled') {
                return ApiResponse::error('Booking đã bị hủy', null, 400);
            }

            if ($booking->status !== 'confirmed') {
                return ApiResponse::error('Booking chưa được chủ sân xác nhận', null, 400);
            }

            // 🎯 LOGIC MỚI: Ưu tiên dùng bank info của Owner
            $owner = $booking->field->fieldCluster->owner ?? null;

            $description = "Dat san " . $booking->field->fieldCluster->name . " " . $booking->day_book->format('d/m/Y');

            // Kiểm tra owner có bank info chưa
            if ($owner && $owner->hasBankInfo()) {
                // ✅ Dùng tài khoản của Owner
                $qrData = $this->vietqrService->createQRForOwner(
                    $owner->getBankInfo(),
                    $booking->id,
                    $booking->total_price,
                    $description
                );

                Log::info('💰 Payment to Owner account', [
                    'booking_id' => $booking->id,
                    'owner_id' => $owner->id,
                    'owner_account' => $owner->account_no,
                ]);

            } else {
                // ⚠️ Fallback: Dùng tài khoản Platform từ .env
                $qrData = $this->vietqrService->createQRCode(
                    $booking->id,
                    $booking->total_price,
                    $description
                );

                Log::warning('⚠️ Payment to Platform account (Owner chưa có bank info)', [
                    'booking_id' => $booking->id,
                    'owner_id' => $owner?->id ?? 'N/A',
                ]);
            }

            // Cập nhật booking với payment method
            $booking->update([
                'payment_method' => 'vietqr',
            ]);

            Log::info('✅ VietQR payment created', [
                'booking_id' => $booking->id,
                'user_id' => $user->id,
                'amount' => $booking->total_price,
            ]);

            return ApiResponse::success('Tạo mã QR thanh toán thành công', [
                'booking_id' => $booking->id,
                'qr_code_url' => $qrData['qr_code_url'],
                'amount' => $booking->total_price,
                'amount_formatted' => $this->vietqrService->formatAmount($booking->total_price),
                'transfer_content' => $qrData['add_info'],
                'account_info' => [
                    'bank_id' => $qrData['bank_id'],
                    'account_no' => $qrData['account_no'],
                    'account_name' => $qrData['account_name'],
                    'is_owner_account' => $qrData['is_owner_account'] ?? false, // 🎯 Flag
                ],
                'instruction' => "Vui lòng quét mã QR hoặc chuyển khoản đúng số tiền và nội dung. Sau đó upload bill để xác nhận thanh toán.",
                'next_step' => 'upload_bill',
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ VietQR create payment error', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ApiResponse::error('Lỗi tạo mã QR: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * 🏆 Tạo VietQR Code để thanh toán giải đấu
     *
     * POST /api/vietqr/create-payment/tournament/{tournamentId}
     */
    public function createPaymentForTournament(Request $request, $tournamentId)
    {
        try {
            $user = auth('api')->user();
            $tournament = \App\Models\Tournament::with('fieldCluster.owner')->find($tournamentId);

            // Validate tournament
            if (!$tournament) {
                return ApiResponse::error('Không tìm thấy giải đấu', null, 404);
            }

            if ($tournament->organizer_id !== $user->id) {
                return ApiResponse::error('Bạn không có quyền thanh toán giải đấu này', null, 403);
            }

            if ($tournament->payment_status === 'paid') {
                return ApiResponse::error('Giải đấu đã được thanh toán', null, 400);
            }

            if ($tournament->status === 'cancelled') {
                return ApiResponse::error('Giải đấu đã bị hủy', null, 400);
            }

            if ($tournament->payment_method !== 'vietqr') {
                return ApiResponse::error('Giải đấu không sử dụng phương thức thanh toán VietQR', null, 400);
            }

            // Lấy tổng tiền từ các booking của giải
            $totalAmount = $tournament->fieldBookings()->sum('total_price') ?? 0;

            if ($totalAmount <= 0) {
                return ApiResponse::error('Giải đấu chưa có booking sân nào', null, 400);
            }

            // Lấy owner của cụm sân
            $owner = $tournament->fieldCluster->owner ?? null;

            if (!$owner) {
                return ApiResponse::error('Không tìm thấy chủ sân', null, 404);
            }

            $description = "Thanh toan giai dau " . $tournament->name;

            // Kiểm tra owner có bank info chưa
            if ($owner->hasBankInfo()) {
                // ✅ Dùng tài khoản của Owner
                $qrData = $this->vietqrService->createQRForOwner(
                    $owner->getBankInfo(),
                    "T{$tournament->id}",
                    $totalAmount,
                    $description
                );

                Log::info('💰 Tournament payment to Owner account', [
                    'tournament_id' => $tournament->id,
                    'owner_id' => $owner->id,
                    'owner_account' => $owner->account_no,
                    'amount' => $totalAmount,
                ]);

            } else {
                return ApiResponse::error(
                    'Chủ sân chưa cấu hình thông tin ngân hàng. Vui lòng liên hệ chủ sân hoặc chọn phương thức thanh toán tiền mặt.',
                    null,
                    400
                );
            }

            Log::info('✅ VietQR tournament payment created', [
                'tournament_id' => $tournament->id,
                'user_id' => $user->id,
                'amount' => $totalAmount,
            ]);

            return ApiResponse::success('Tạo mã QR thanh toán giải đấu thành công', [
                'tournament_id' => $tournament->id,
                'qr_code_url' => $qrData['qr_code_url'],
                'amount' => $totalAmount,
                'amount_formatted' => $this->vietqrService->formatAmount($totalAmount),
                'transfer_content' => $qrData['add_info'],
                'account_info' => [
                    'bank_id' => $qrData['bank_id'],
                    'account_no' => $qrData['account_no'],
                    'account_name' => $qrData['account_name'],
                    'is_owner_account' => true,
                ],
                'instruction' => "Vui lòng quét mã QR hoặc chuyển khoản đúng số tiền và nội dung. Sau đó upload bill qua API /api/tournaments/{$tournament->id}/upload-payment-proof để xác nhận thanh toán.",
                'next_step' => 'upload_bill',
                'upload_api' => "/api/tournaments/{$tournament->id}/upload-payment-proof",
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ VietQR create tournament payment error', [
                'tournament_id' => $tournamentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ApiResponse::error('Lỗi tạo mã QR: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * 📄 Kiểm tra trạng thái thanh toán của booking
     *
     * GET /api/vietqr/check-status/{bookingId}
     */
    public function checkStatus($bookingId)
    {
        try {
            $user = auth('api')->user();
            $booking = Book::with('field.fieldCluster')->find($bookingId);

            if (!$booking) {
                return ApiResponse::error('Không tìm thấy booking', null, 404);
            }

            if ($booking->user_id !== $user->id && $user->role->name_role !== 'admin') {
                return ApiResponse::error('Bạn không có quyền xem thông tin này', null, 403);
            }

            return ApiResponse::success('Thông tin trạng thái thanh toán', [
                'booking_id' => $booking->id,
                'payment_status' => $booking->payment_status,
                'payment_method' => $booking->payment_method,
                'payment_proof' => $booking->payment_proof,
                'payment_confirmed_at' => $booking->payment_confirmed_at,
                'payment_note' => $booking->payment_note,
                'amount' => $booking->total_price,
                'status_message' => match($booking->payment_status) {
                    'pending' => $booking->payment_proof
                        ? 'Đã upload bill, đang chờ xác nhận từ chủ sân'
                        : 'Chưa thanh toán. Vui lòng chuyển khoản và upload bill.',
                    'paid' => 'Đã thanh toán thành công',
                    'failed' => 'Thanh toán thất bại hoặc bị từ chối',
                    default => 'Không xác định',
                },
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ Check status error', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error('Lỗi kiểm tra trạng thái: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * 🏦 Lấy danh sách ngân hàng hỗ trợ VietQR
     *
     * GET /api/vietqr/banks
     */
    public function getBanks()
    {
        try {
            $banks = $this->vietqrService->getBankList();

            // Trả về array trực tiếp để frontend dễ sử dụng
            return ApiResponse::success('Danh sách ngân hàng', $banks, 200);

        } catch (\Exception $e) {
            Log::error('❌ Get banks error', [
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error('Lỗi lấy danh sách ngân hàng: ' . $e->getMessage(), null, 500);
        }
    }
}
