<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class VietQRService
{
    private string $bankId;
    private string $accountNo;
    private string $accountName;
    private ?string $clientId;
    private ?string $apiKey;
    private string $template;
    private string $apiUrl;

    public function __construct()
    {
        $this->bankId = config('services.vietqr.bank_id') ?? '970422';
        $this->accountNo = config('services.vietqr.account_no') ?? '';
        $this->accountName = config('services.vietqr.account_name') ?? '';
        $this->clientId = config('services.vietqr.client_id');
        $this->apiKey = config('services.vietqr.api_key');
        $this->template = config('services.vietqr.template') ?? 'compact';
        $this->apiUrl = config('services.vietqr.api_url') ?? 'https://api.vietqr.io/v2';
    }

    /**
     * Tạo VietQR code URL
     *
     * @param string $bookingId ID của booking
     * @param float $amount Số tiền cần thanh toán
     * @param string $description Nội dung chuyển khoản
     * @return array
     */
    public function createQRCode($bookingId, $amount, $description = '')
    {
        $addInfo = $description ?: "BOOK-{$bookingId}";

        // Nếu có API key, dùng VietQR API để tạo QR động
        if ($this->clientId && $this->apiKey) {
            return $this->createQRViaAPI($amount, $addInfo, $bookingId);
        }

        // Không có API key → Tạo QR URL cơ bản (dùng API free)
        return $this->createQRViaURL($amount, $addInfo, $bookingId);
    }

    /**
     * Tạo QR code qua VietQR API (nếu có credentials)
     */
    private function createQRViaAPI($amount, $addInfo, $bookingId)
    {
        try {
            $response = Http::withHeaders([
                'x-client-id' => $this->clientId,
                'x-api-key' => $this->apiKey,
            ])->post($this->apiUrl . '/generate', [
                'accountNo' => $this->accountNo,
                'accountName' => $this->accountName,
                'acqId' => $this->bankId,
                'amount' => (int)$amount,
                'addInfo' => $addInfo,
                'format' => 'text',
                'template' => $this->template,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('✅ VietQR created via API', [
                    'booking_id' => $bookingId,
                    'amount' => $amount,
                    'qr_code' => $data['data']['qrCode'] ?? null,
                ]);

                return [
                    'qr_code_url' => $data['data']['qrDataURL'] ?? null,
                    'qr_code' => $data['data']['qrCode'] ?? null,
                    'amount' => $amount,
                    'add_info' => $addInfo,
                    'account_no' => $this->accountNo,
                    'account_name' => $this->accountName,
                    'bank_id' => $this->bankId,
                ];
            }

            // Nếu API fail, fallback về URL
            Log::warning('⚠️ VietQR API failed, fallback to URL method');
            return $this->createQRViaURL($amount, $addInfo, $bookingId);

        } catch (\Exception $e) {
            Log::error('❌ VietQR API error', [
                'error' => $e->getMessage(),
            ]);

            // Fallback về URL method
            return $this->createQRViaURL($amount, $addInfo, $bookingId);
        }
    }

    /**
     * Tạo QR code qua URL (API free - không cần đăng ký)
     */
    private function createQRViaURL($amount, $addInfo, $bookingId)
    {
        // Format: https://img.vietqr.io/image/{BANK_ID}-{ACCOUNT_NO}-{TEMPLATE}.png?amount={AMOUNT}&addInfo={INFO}&accountName={NAME}
        $qrUrl = sprintf(
            'https://img.vietqr.io/image/%s-%s-%s.png?amount=%d&addInfo=%s&accountName=%s',
            $this->bankId,
            $this->accountNo,
            $this->template,
            (int)$amount,
            urlencode($addInfo),
            urlencode($this->accountName)
        );

        Log::info('✅ VietQR created via URL', [
            'booking_id' => $bookingId,
            'amount' => $amount,
            'qr_url' => $qrUrl,
        ]);

        return [
            'qr_code_url' => $qrUrl,
            'qr_code' => null, // URL method không trả về QR code string
            'amount' => $amount,
            'add_info' => $addInfo,
            'account_no' => $this->accountNo,
            'account_name' => $this->accountName,
            'bank_id' => $this->bankId,
        ];
    }

    /**
     * 🆕 Tạo VietQR cho Owner cụ thể
     *
     * @param array $ownerBankInfo ['bank_id', 'account_no', 'account_name']
     * @param string $bookingId
     * @param float $amount
     * @param string $description
     * @return array
     */
    public function createQRForOwner($ownerBankInfo, $bookingId, $amount, $description = '')
    {
        $addInfo = $description ?: "BOOK-{$bookingId}";

        // Validate owner bank info
        if (empty($ownerBankInfo['bank_id']) ||
            empty($ownerBankInfo['account_no']) ||
            empty($ownerBankInfo['account_name'])) {
            throw new \Exception('Owner chưa cập nhật thông tin tài khoản ngân hàng');
        }

        // Tạo QR URL với thông tin của Owner
        $qrUrl = sprintf(
            'https://img.vietqr.io/image/%s-%s-%s.png?amount=%d&addInfo=%s&accountName=%s',
            $ownerBankInfo['bank_id'],
            $ownerBankInfo['account_no'],
            $this->template,
            (int)$amount,
            urlencode($addInfo),
            urlencode($ownerBankInfo['account_name'])
        );

        Log::info('✅ VietQR created for Owner', [
            'booking_id' => $bookingId,
            'amount' => $amount,
            'owner_account' => $ownerBankInfo['account_no'],
            'qr_url' => $qrUrl,
        ]);

        return [
            'qr_code_url' => $qrUrl,
            'qr_code' => null,
            'amount' => $amount,
            'add_info' => $addInfo,
            'account_no' => $ownerBankInfo['account_no'],
            'account_name' => $ownerBankInfo['account_name'],
            'bank_id' => $ownerBankInfo['bank_id'],
            'is_owner_account' => true, // 🎯 Flag để biết đây là TK owner
        ];
    }

    /**
     * 🔧 Format số tiền
     */
    public function formatAmount($amount)
    {
        return number_format($amount, 0, ',', '.') . ' VNĐ';
    }

    /**
     * Lấy danh sách ngân hàng hỗ trợ VietQR
     */
    public function getBankList()
    {
        try {
            $response = Http::get($this->apiUrl . '/banks');

            if ($response->successful()) {
                return $response->json()['data'] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::error('❌ Get bank list error', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Validate thông tin chuyển khoản (so sánh với nội dung mong đợi)
     */
    public function validateTransferInfo($bookingId, $transferContent)
    {
        $expectedContent = "BOOK-{$bookingId}";

        // Chuẩn hóa chuỗi
        $transferContent = strtoupper(trim($transferContent));
        $expectedContent = strtoupper(trim($expectedContent));

        // So sánh
        return str_contains($transferContent, $expectedContent);
    }

    /**
     * Loại bỏ dấu tiếng Việt (nếu cần cho nội dung CK)
     */
    private function removeVietnamese($str)
    {
        $unicode = [
            'a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',
            'd' => 'đ',
            'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
            'i' => 'í|ì|ỉ|ĩ|ị',
            'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
            'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
            'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
            'A' => 'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ặ|Ằ|Ẳ|Ẵ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
            'D' => 'Đ',
            'E' => 'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
            'I' => 'Í|Ì|Ỉ|Ĩ|Ị',
            'O' => 'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
            'U' => 'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
            'Y' => 'Ý|Ỳ|Ỷ|Ỹ|Ỵ',
        ];

        foreach ($unicode as $nonUnicode => $uni) {
            $str = preg_replace("/($uni)/i", $nonUnicode, $str);
        }

        return $str;
    }
}
